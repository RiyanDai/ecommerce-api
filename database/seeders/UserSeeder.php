<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // change in production
            'role' => 'admin',
        ]);

        // 3 customers
        User::create([
            'name' => 'Customer One',
            'email' => 'customer1@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        User::create([
            'name' => 'Customer Two',
            'email' => 'customer2@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        User::create([
            'name' => 'Customer Three',
            'email' => 'customer3@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
    }
}