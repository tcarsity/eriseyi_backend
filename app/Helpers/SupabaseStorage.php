<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SupabaseStorage
{
    public static function upload($file, $path)
    {

        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        // $file is an UploadedFile or Illuminate\Http\File
        $filePath = $file->getPathname();
        $extension = $file->getClientOriginalExtension();

        $fileName = time() . '_' . uniqid() . '.' . $extension;
        $fullPath = "$path/$fileName";

        // Correct MIME detection
        $mime = mime_content_type($filePath);

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
