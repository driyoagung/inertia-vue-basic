<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach ([
            ['name' => 'Frontend', 'color' => '#38bdf8'],
            ['name' => 'Backend', 'color' => '#a78bfa'],
            ['name' => 'Design', 'color' => '#f472b6'],
            ['name' => 'Laravel', 'color' => '#fb7185'],
            ['name' => 'Vue', 'color' => '#34d399'],
            ['name' => 'In Progress', 'color' => '#fbbf24'],
            ['name' => 'Documentation', 'color' => '#94a3b8'],
            ['name' => 'High Priority', 'color' => '#f87171'],
        ] as $tag) {
            Tag::query()->firstOrCreate(['name' => $tag['name']], ['color' => $tag['color']]);
        }
    }
}
