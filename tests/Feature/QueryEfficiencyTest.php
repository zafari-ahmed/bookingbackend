<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the eager-loading contract on the two heaviest screens.
 *
 * The assertions are on query *counts* rather than on `with()` calls, so they
 * fail if someone later adds a relation to a Blade loop without loading it.
 */
class QueryEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->officer = User::factory()->inDepartments($this->department)->create();
    }

    public function test_the_dashboard_case_list_does_not_grow_queries_with_rows(): void
    {
        $this->makeCases(3);
        $withThree = $this->countQueries(fn () => $this->get(route('dashboard'))->assertOk());

        $this->makeCases(12);
        $withFifteen = $this->countQueries(fn () => $this->get(route('dashboard'))->assertOk());

        $this->assertSame(
            $withThree,
            $withFifteen,
            'The dashboard fired more queries for more rows, which means a relation is being lazy-loaded in the table loop.',
        );
    }

    public function test_the_case_detail_page_does_not_grow_queries_with_remarks(): void
    {
        $case = $this->makeCases(1)->sole();

        $this->makeComments($case, 2);
        $withTwo = $this->countQueries(fn () => $this->get(route('cases.show', $case))->assertOk());

        $this->makeComments($case, 8);
        $withTen = $this->countQueries(fn () => $this->get(route('cases.show', $case))->assertOk());

        $this->assertSame(
            $withTwo,
            $withTen,
            'The Case Detail page fired more queries for more remarks — check the comment thread eager loads.',
        );
    }

    public function test_the_case_register_is_paginated_rather_than_fully_loaded(): void
    {
        $this->makeCases(45);

        $response = $this->actingAs($this->officer)
            ->get(route('cases.index'))
            ->assertOk();

        $cases = $response->viewData('cases');

        $this->assertSame((int) config('cases.per_page', 20), $cases->perPage());
        $this->assertCount($cases->perPage(), $cases->items());
        $this->assertSame(45, $cases->total());
    }

    /**
     * @return Collection<int, CaseModel>
     */
    private function makeCases(int $count)
    {
        return CaseModel::factory()
            ->count($count)
            ->create([
                'department_id' => $this->department->id,
                'created_by' => User::factory()->inDepartments($this->department)->create()->id,
                'assigned_to_user_id' => User::factory()->inDepartments($this->department)->create()->id,
            ]);
    }

    private function makeComments(CaseModel $case, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            CaseComment::factory()->create([
                'case_id' => $case->id,
                'department_id' => $this->department->id,
                'user_id' => User::factory()->inDepartments($this->department)->create()->id,
            ]);
        }
    }

    private function countQueries(callable $callback): int
    {
        $this->actingAs($this->officer->refresh());

        // The dashboard stat cards are cached per user, so the count has to be
        // taken with a warm cache on both runs to be comparable.
        $callback();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $callback();

        return $queries;
    }
}
