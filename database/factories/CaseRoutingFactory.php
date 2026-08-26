<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoutingAction;
use App\Models\CaseModel;
use App\Models\CaseRouting;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseRouting>
 */
class CaseRoutingFactory extends Factory
{
    protected $model = CaseRouting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => CaseModel::factory(),
            'department_id' => Department::factory(),
            'assigned_by' => User::factory(),
            'assigned_to_user_id' => null,
            'action' => RoutingAction::Assigned,
            'notes' => $this->faker->sentence(10),
        ];
    }

    public function action(RoutingAction $action): static
    {
        return $this->state(fn (): array => ['action' => $action]);
    }
}
