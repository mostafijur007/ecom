<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
        
        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📧 Login Credentials:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Admin:     admin@ecommerce.com / password');
        $this->command->info('Vendor 1:  vendor1@ecommerce.com / password');
        $this->command->info('Vendor 2:  vendor2@ecommerce.com / password');
        $this->command->info('Vendor 3:  vendor3@ecommerce.com / password');
        $this->command->info('Vendor 4:  vendor4@ecommerce.com / password');
        $this->command->info('Customer:  john@example.com / password');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
