<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $email = env('SEED_SUPERADMIN_EMAIL', 'superadmin@example.com');

        $exists = User::where('email', $email)->first();
        if ($exists) {
            $this->command->info("Superadmin already exists: {$email}");
            return;
        }

        User::create([
            'name' => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
            'email' => $email,
            'password' => Hash::make(env('SEED_SUPERADMIN_PASSWORD', 'orisunayo2006')),
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $this->command->info("Superadmin created: {$email}");
    }
}


