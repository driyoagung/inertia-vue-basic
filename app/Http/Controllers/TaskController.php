<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::with(['category', 'assignee'])
            ->latest()
            ->limit(25)
            ->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'color']),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:pending,in_progress,done'],
        ]);

        Task::create($data);

        return back()->with('success', 'Task berhasil dibuat.');
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($w) use ($query) {
                    $w->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $users,
        ]);
    }
}
