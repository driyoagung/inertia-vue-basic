<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KanbanTask;

class KanbanTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            ['title' => 'Learn Vue 3 Basics', 'status' => 'done', 'order' => 1],
            ['title' => 'Setup Laravel Inertia', 'status' => 'done', 'order' => 2],
            ['title' => 'Implement Kanban Board UI', 'status' => 'in_progress', 'order' => 1],
            ['title' => 'Add Drag and Drop Library', 'status' => 'in_progress', 'order' => 2],
            ['title' => 'Create Backend Bulk Update API', 'status' => 'todo', 'order' => 1],
            ['title' => 'Write Documentation', 'status' => 'todo', 'order' => 2],
            ['title' => 'Deploy to Production', 'status' => 'todo', 'order' => 3],
        ];

        foreach ($tasks as $task) {
            KanbanTask::create($task);
        }
    }
}
