<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Frontend Development', 'color' => '#3b82f6'],
            ['name' => 'Backend Development', 'color' => '#10b981'],
            ['name' => 'Mobile Development', 'color' => '#f59e0b'],
            ['name' => 'UI/UX Design', 'color' => '#ec4899'],
            ['name' => 'DevOps', 'color' => '#8b5cf6'],
            ['name' => 'Quality Assurance', 'color' => '#ef4444'],
            ['name' => 'Data Engineering', 'color' => '#06b6d4'],
            ['name' => 'Machine Learning', 'color' => '#84cc16'],
            ['name' => 'Project Management', 'color' => '#6366f1'],
            ['name' => 'Documentation', 'color' => '#64748b'],
            ['name' => 'Customer Support', 'color' => '#f43f5e'],
            ['name' => 'Marketing', 'color' => '#eab308'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                ['name' => $cat['name'], 'color' => $cat['color']],
            );
        }
    }
}
