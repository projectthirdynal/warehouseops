<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Hardware', 'slug' => 'hardware', 'color' => 'red'],
            ['name' => 'Software', 'slug' => 'software', 'color' => 'blue'],
            ['name' => 'Network', 'slug' => 'network', 'color' => 'green'],
            ['name' => 'Access / Perms', 'slug' => 'access', 'color' => 'yellow'],
            ['name' => 'Other', 'slug' => 'other', 'color' => 'gray'],
        ];

        foreach ($categories as $category) {
            TicketCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
