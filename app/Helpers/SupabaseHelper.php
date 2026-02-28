<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseHelper
{


    private static function client()
    {

        return Http::withHeaders([

            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),

            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),

            'Content-Type' => 'application/json',

        ]);

    }


    private static function baseUrl()
    {

        return rtrim(env('SUPABASE_URL'), '/');

    }



    // public static function invite($email)
    // {

    //     return Http::withHeaders([

    //         'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),

    //         'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),

    //         'Content-Type' => 'application/json',

    //     ])->post(
    //         env('SUPABASE_URL') . '/auth/v1/invite?redirect_to=' . urlencode(env('FRONTEND_URL') . '/reset-password'),
    //         [

    //         'email' => $email,

    //         ]
    //     );

    // }

    public static function invite($email)
    {

        $response = self::client()->post(

            self::baseUrl() . '/auth/v1/invite?redirect_to=' . urlencode(env('FRONTEND_URL') . '/reset-password'),

            ['email' => $email]

        );

        if (!$response->successful()) {

            Log::error('Supabase invite failed', [

                'response' => $response->body()

            ]);

            return false;

        }

        return true;

    }




    public static function updateEmail($oldEmail, $newEmail)
    {

        $response = self::client()->get(

            self::baseUrl() . '/auth/v1/admin/users',

            ['email' => $oldEmail]

        );


        if (!$response->successful()) {

            Log::error('Supabase fetch user failed', [

                'response' => $response->body()

            ]);

            return false;

        }

        $user = $response->json()['users'][0] ?? null;

        if (!$user) {

            Log::error('Supabase user not found: ' . $oldEmail);

            return false;

        }

        $update = self::client()->put(

            self::baseUrl() . '/auth/v1/admin/users/' . $user['id'],

            ['email' => $newEmail]

        );


        if (!$update->successful()) {

            Log::error('Supabase email update failed', [

                'response' => $update->body()

            ]);

            return false;

        }

        return true;

    }



    public static function deleteUser($email)
    {

        $response = self::client()->get(

            self::baseUrl() . '/auth/v1/admin/users',

            ['email' => $email]

        );


        if (!$response->successful()) {

            Log::error('Supabase fetch user for delete failed', [

                'response' => $response->body()

            ]);

            return false;

        }

        $user = $response->json()['users'][0] ?? null;


        if (!$user) {

            Log::error('Supabase user not found for delete: ' . $email);

            return false;

        }


        $delete = self::client()->delete(

            self::baseUrl() . '/auth/v1/admin/users/' . $user['id']

        );



        if (!$delete->successful()) {

            Log::error('Supabase delete failed', [

                'response' => $delete->body()

            ]);

            return false;

        }

        return true;

    }



}
