<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Http;

class SupabaseHelper
{

    public static function invite($email)
    {

        return Http::withHeaders([

            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),

            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),

            'Content-Type' => 'application/json',

        ])->post(
            env('SUPABASE_URL') . '/auth/v1/invite?redirect_to=' . urlencode(env('FRONTEND_URL') . '/reset-password'),
            [

            'email' => $email,

            ]
        );

    }

    public static function updateEmail($oldEmail, $newEmail)
    {

        $headers = [

            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',

        ];

        // Get user by email

        $response = Http::withHeaders($headers)

            ->get(env('SUPABASE_URL') . '/auth/v1/admin/users', [

                'email' => $oldEmail

            ]);


        if (!$response->successful()) {

            \Log::error('Supabase fetch user failed', [

                'response' => $response->body()

            ]);

            return;

        }

        $user = $response->json()['users'][0] ?? null;

        if (!$user) {

            \Log::error('Supabase user not found for email: ' . $oldEmail);

            return;

        }

        // Update email

        $update = Http::withHeaders($headers)

            ->put(env('SUPABASE_URL') . '/auth/v1/admin/users/' . $user['id'], [

                'email' => $newEmail

            ]);


        if (!$update->successful()) {

            \Log::error('Supabase email update failed', [

                'response' => $update->body()

            ]);

        }

    }

}
