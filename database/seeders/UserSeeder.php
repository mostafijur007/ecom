<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Vendors
        $vendors = [
            [
                'name' => 'TechVendor Solutions',
                'email' => 'vendor@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Fashion House',
                'email' => 'vendor2@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Home Essentials Inc',
                'email' => 'vendor3@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sports Pro',
                'email' => 'vendor4@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($vendors as $vendor) {
            User::create($vendor);
        }

        // Create Customers
        $customers = [
            [
                'name' => 'Md Mostafijur Rahman',
                'email' => 'mostafijur@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'customer@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Alice Williams',
                'email' => 'alice@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($customers as $customer) {
            User::create($customer);
        }

        $this->command->info('Users seeded successfully!');
    }
}
