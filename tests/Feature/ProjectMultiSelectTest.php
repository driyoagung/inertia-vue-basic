<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMultiSelectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    public function test_project_page_shows_available_tags_and_existing_projects(): void
    {
        Tag::query()->create(['name' => 'Backend', 'color' => '#a78bfa']);

        $this->get(route('projects.multi-select'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/MultiSelect')
                ->has('tags', 1)
                ->has('projects', 0));
    }

    public function test_project_can_be_created_with_multiple_tags(): void
    {
        $backend = Tag::query()->create(['name' => 'Backend', 'color' => '#a78bfa']);
        $frontend = Tag::query()->create(['name' => 'Frontend', 'color' => '#38bdf8']);

        $this->post(route('projects.multi-select.store'), [
            'name' => 'Inventory portal',
            'description' => 'Internal inventory management',
            'tag_ids' => [$backend->id, $frontend->id],
        ])->assertRedirect(route('projects.multi-select'));

        $project = Project::query()->firstOrFail();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Inventory portal',
        ]);
        $this->assertDatabaseHas('project_tag', [
            'project_id' => $project->id,
            'tag_id' => $backend->id,
        ]);
        $this->assertDatabaseHas('project_tag', [
            'project_id' => $project->id,
            'tag_id' => $frontend->id,
        ]);
    }

    public function test_unknown_tag_ids_are_rejected(): void
    {
        $this->from(route('projects.multi-select'))
            ->post(route('projects.multi-select.store'), [
                'name' => 'Invalid project',
                'tag_ids' => [999],
            ])
            ->assertSessionHasErrors('tag_ids.0');

        $this->assertDatabaseCount('projects', 0);
    }
}
