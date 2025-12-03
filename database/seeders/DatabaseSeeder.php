<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(SuperAdminSeeder::class);


        User::factory()->create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'role' => 'admin',
            'password' => Hash::make('passwordone')
        ]);

         User::factory()->create([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'role' => 'admin',
            'password' => Hash::make('passwordtwo')
        ]);
    }
}
