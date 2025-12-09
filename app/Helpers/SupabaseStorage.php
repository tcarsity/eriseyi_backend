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

        $fileName = time() . '_' . basename($file);
        $fullPath = $path . '/' . $fileName;

        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type' => $file->getClientMimeType(),
            'x-upsert' => 'true',
        ])->put(
            "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fullPath}",
            file_get_contents($file)
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
