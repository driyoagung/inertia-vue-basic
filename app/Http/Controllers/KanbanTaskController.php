<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\KanbanTask;

class KanbanTaskController extends Controller
{
    public function index()
    {
        // Fetch all tasks ordered by their assigned order
        $tasks = KanbanTask::orderBy('order')->get();
        
        return Inertia::render('Kanban/Index', [
            'tasks' => $tasks
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:kanban_tasks,id',
            'tasks.*.status' => 'required|string',
            'tasks.*.order' => 'required|integer',
        ]);

        // In a real application, bulk updating using raw SQL or batching is better for performance.
        // For simplicity in this study case, we'll loop through the tasks.
        foreach ($request->tasks as $taskData) {
            KanbanTask::where('id', $taskData['id'])->update([
                'status' => $taskData['status'],
                'order' => $taskData['order'],
            ]);
        }

        return redirect()->back(); // Inertia will handle the seamless reload
    }
}
