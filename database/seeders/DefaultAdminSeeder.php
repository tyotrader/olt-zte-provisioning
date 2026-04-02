<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultAdminSeeder extends Seeder
{
    public function run()
    {
        // Create default admin user if not exists
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('admin'),
                'fullname' => 'Administrator',
                'email' => 'admin@localhost',
                'role' => 'admin',
                'is_active' => true
            ]
        );
        
        $this->command->info('Default admin user created/verified.');
        $this->command->info('Username: admin');
        $this->command->info('Password: admin');
    }
}
