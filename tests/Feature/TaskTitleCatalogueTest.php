<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskTitleCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_task_with_a_new_title_adds_it_to_the_catalogue(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), ['title' => 'Brand new chore'])
            ->assertRedirect();

        $template = TaskTemplate::firstWhere('name', 'Brand new chore');
        $this->assertNotNull($template);
        $this->assertSame(1, $template->usage_count);
        $this->assertSame($user->id, $template->created_by);
    }

    public function test_creating_a_task_with_a_known_title_increments_its_usage_count(): void
    {
        $user = User::factory()->create();
        $template = TaskTemplate::factory()->create(['name' => 'Staubsaugen', 'usage_count' => 2]);

        $this->actingAs($user)
            ->post(route('tasks.store'), ['title' => 'Staubsaugen'])
            ->assertRedirect();

        $this->assertSame(3, $template->fresh()->usage_count);
        // No duplicate catalogue row was created for the same name.
        $this->assertSame(1, TaskTemplate::where('name', 'Staubsaugen')->count());
    }

    public function test_matching_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $template = TaskTemplate::factory()->create(['name' => 'Staubsaugen', 'usage_count' => 0]);

        $this->actingAs($user)
            ->post(route('tasks.store'), ['title' => 'staubsaugen'])
            ->assertRedirect();

        $this->assertSame(1, $template->fresh()->usage_count);
        $this->assertSame(1, TaskTemplate::count());
    }

    public function test_templates_are_shared_on_the_pages_with_the_create_form(): void
    {
        $user = User::factory()->create();
        TaskTemplate::factory()->create(['name' => 'Bad putzen']);

        $this->actingAs($user)->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->has('templates', 1));

        $this->actingAs($user)->get(route('tasks.index'))
            ->assertInertia(fn (Assert $page) => $page->has('templates', 1));

        $this->actingAs($user)->get(route('upcoming'))
            ->assertInertia(fn (Assert $page) => $page->has('templates', 1));
    }

    public function test_templates_are_ordered_most_used_first(): void
    {
        $user = User::factory()->create();
        TaskTemplate::factory()->create(['name' => 'Selten', 'usage_count' => 1]);
        TaskTemplate::factory()->create(['name' => 'Oft', 'usage_count' => 9]);

        $this->actingAs($user)->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('templates.0.name', 'Oft'));
    }

    public function test_creating_a_task_still_works_normally(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), ['title' => 'Normale Aufgabe'])
            ->assertRedirect();

        $this->assertNotNull(Task::firstWhere('title', 'Normale Aufgabe'));
    }
}
