<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\CaseActivityLog;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseActivityLog>
 */
class CaseActivityLogFactory extends Factory
{
    protected $model = CaseActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => CaseModel::factory(),
            'user_id' => User::factory(),
            'action_type' => ActivityType::Created,
            'description' => $this->faker->sentence(8),
            'meta' => null,
        ];
    }
}
