<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default superadmin if not exists
        if (!User::where('username', 'admin')->exists()) {
            User::create([
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@warehouse.local',
                'password' => Hash::make('admin'),
                'role' => User::ROLE_SUPERADMIN,
                'is_active' => true,
            ]);
        }

        // Create sample users for each role (optional)
        $sampleUsers = [
            [
                'name' => 'System Administrator',
                'username' => 'sysadmin',
                'email' => 'sysadmin@warehouse.local',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Warehouse Operator',
                'username' => 'operator',
                'email' => 'operator@warehouse.local',
                'role' => User::ROLE_OPERATOR,
            ],
            [
                'name' => 'Delivery Agent',
                'username' => 'agent',
                'email' => 'agent@warehouse.local',
                'role' => User::ROLE_AGENT,
            ],
        ];

        foreach ($sampleUsers as $userData) {
            if (!User::where('username', $userData['username'])->exists()) {
                User::create([
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password123'),
                    'role' => $userData['role'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
