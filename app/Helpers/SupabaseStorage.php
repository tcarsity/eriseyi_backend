<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SupabaseStorage
{
    public static function upload(string $filePath, string $path)
    {
        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        if (!file_exists($filePath)) {
            throw new \Exception('Temp file does not exist');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fileName = time().'_'.uniqid().'.'.$extension;
        $fullPath = $path.'/'.$fileName;

        $mime = mime_content_type($filePath);

        $response = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer '.$supabaseKey,
            'Content-Type' => $mime,
            'x-upsert' => 'true',
        ])->withBody(
            file_get_contents($filePath),
            $mime
        )->put(
            "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fullPath}"
        );

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$fullPath}";
    }


   public static function delete($path)
    {
        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $supabaseKey = env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET');

        $relativePath = str_replace(
            "$supabaseUrl/storage/v1/object/public/$bucket/",
            "",
            $path
        );

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
