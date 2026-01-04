<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CheckerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $checkers = [
            ['username' => 'checker-carlo', 'name' => 'Carlo (Checker)', 'role' => 'checker'],
            ['username' => 'checker-ced', 'name' => 'Ced (Checker)', 'role' => 'checker'],
            ['username' => 'checker-joel', 'name' => 'Joel (Checker)', 'role' => 'checker'],
        ];

        foreach ($checkers as $checker) {
            User::firstOrCreate(
                ['username' => $checker['username']],
                [
                    'name' => $checker['name'],
                    'email' => $checker['username'] . '@warehouse.local',
                    'password' => Hash::make('password123'),
                    'role' => $checker['role'],
                    'is_active' => true,
                ]
            );
            $this->command->info("Created Checker: {$checker['username']}");
        }
    }
}
