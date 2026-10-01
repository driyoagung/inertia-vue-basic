<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Projects/MultiSelect', [
            'success' => session('success'),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'projects' => Project::query()
                ->with(['tags:id,name,color'])
                ->latest()
                ->limit(10)
                ->get(['id', 'name', 'description', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tag_ids' => ['nullable', 'array', 'max:10'],
            'tag_ids.*' => ['required', 'integer', 'distinct', 'exists:tags,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            $project = Project::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $project->tags()->sync($validated['tag_ids'] ?? []);
        });

        return to_route('projects.multi-select')
            ->with('success', 'Project berhasil dibuat dengan tag yang dipilih.');
    }
}
