<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseComment>
 */
class CaseCommentFactory extends Factory
{
    protected $model = CaseComment::class;

    /**
     * @var array<int, string>
     */
    public const REMARKS = [
        'Application received at the front desk and verified against the domicile record.',
        'Sub-division staff visited the site and confirmed the complaint on the ground.',
        'Beat officer deputed. Request the department to share the notice copy in advance.',
        'Notice issued under the relevant section and shared with both departments.',
        'Confirming the plot falls within the surveyed area. No departmental objection.',
        'Field report attached. Recommend immediate remedial action by the concerned wing.',
        'Complainant contacted by telephone and informed of the current status.',
        'Matter placed before the departmental committee for a decision this week.',
        'Funds requisition moved to the district accounts office; awaiting release.',
        'Inspection scheduled for the coming Monday with the complainant present.',
        'Records retrieved from the registry; the earlier file had been misplaced.',
        'Interim relief arranged while the permanent repair is tendered.',
        'Referred onward as the subject falls outside this department\'s mandate.',
        'Compliance report submitted to the Assistant Commissioner for review.',
        'Case discussed in the weekly coordination meeting; action points recorded.',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => CaseModel::factory(),
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'comment' => $this->faker->randomElement(self::REMARKS),
            'forwarded_to_department_id' => null,
        ];
    }

    public function forCase(CaseModel $case): static
    {
        return $this->state(fn (): array => ['case_id' => $case->getKey()]);
    }

    public function by(User $user, Department $department): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->getKey(),
            'department_id' => $department->getKey(),
        ]);
    }

    public function forwardingTo(Department $department): static
    {
        return $this->state(fn (): array => [
            'forwarded_to_department_id' => $department->getKey(),
        ]);
    }
}
