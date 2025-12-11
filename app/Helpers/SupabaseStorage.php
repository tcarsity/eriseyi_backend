<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SupabaseStorage
{
    public static function upload($filePath, $path)
    {

        if($filePath instanceof \Illuminate\Http\File)
        {
            $filePath = $filePath->getPathname();
        }

        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $extension;
        $fullPath = $path . '/' . $fileName;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type' => $mime,
            'x-upsert' => 'true',
        ])->put(
            "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fullPath}",
            file_get_contents($filePath)
        );

        if (!$response->successful()) {
            throw new \Exception("Supabase upload failed: " . $response->body());
        }

        // return public URL
        return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$fullPath}";
    }

    public static function delete($path)
    {
        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        // Convert full public URL → relative path inside bucket
        $relativePath = str_replace("$supabaseUrl/storage/v1/object/public/$bucket/", "", $path);

        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
        ])->delete(
            "{$supabaseUrl}/storage/v1/object/$bucket/$relativePath"
        );

        if (!$response->successful()) {
            throw new \Exception("Supabase delete failed: " . $response->body());
        }
    }
}
