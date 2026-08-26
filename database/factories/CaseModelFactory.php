<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CasePriority;
use App\Enums\CaseSource;
use App\Enums\CaseStatus;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CaseModel>
 */
class CaseModelFactory extends Factory
{
    protected $model = CaseModel::class;

    /**
     * Complaint text drawn from the kinds of grievance an AC office actually
     * receives, so the dashboard reads like a real register rather than lorem.
     *
     * @var array<int, string>
     */
    private const ISSUES = [
        'No municipal water supply in the lane for eleven days despite repeated applications.',
        'Encroachment on the public footpath by shop extensions blocking pedestrian access.',
        'Boundary dispute over three acres of agricultural land pending demarcation.',
        'Domicile certificate application pending beyond the statutory thirty days.',
        'Overflowing sewerage outside the primary school gate creating a health hazard.',
        'Benazir Income Support payment not received for three consecutive quarters.',
        'Overcharging of wheat flour above the notified rate at the subsidised outlet.',
        'Streetlights non-functional on the main approach road for over a month.',
        'Illegal water hydrant operating beside a residential block through the night.',
        'Request for issuance of a duplicate land record (Form VII-B).',
        'Teacher absenteeism reported at the Government Girls Primary School.',
        'Sanitary staff not visiting the ward for garbage lifting since Eid.',
        'Ambulance service unavailable at the Rural Health Centre during night hours.',
        'NOC delayed for construction of a boundary wall without stated reason.',
        'Tube well transformer burnt, irrigation to twelve acres halted.',
        'Ration distribution list excludes eligible households in the union council.',
        'Widow pension file lost at the treasury office; pension unpaid four months.',
        'Trade licence renewal fee receipt not issued after payment.',
        'Illegal commercial use of a residential plot in a notified housing scheme.',
        'Request for police protection in an ongoing harassment matter.',
        'Child labour reported at a roadside workshop near the bus terminal.',
        'Disability certificate assessment board has not convened for two months.',
        'Youth skills stipend not disbursed to enrolled trainees.',
        'Stray dog menace near the community park after two reported bites.',
        'Vaccination outreach team has not visited the goth this quarter.',
        'Rescue shelter refused admission to a woman seeking protection.',
        'School building declared unsafe but classes continue in the same block.',
        'Fishing licence renewal blocked without any stated reason.',
        'Local government contractor abandoned the drainage work midway.',
        'Special education centre lacks a trained instructor since March.',
    ];

    /**
     * @var array<int, string>
     */
    private const AREAS = [
        'Latifabad Unit 9, Hyderabad',
        'Qasimabad Housing Scheme, Hyderabad',
        'Gulshan-e-Iqbal Block 6, Karachi East',
        'Village Bakhshapur, Taluka Kotri',
        'Ward 4, Tando Jam Municipal Committee',
        'Goth Allah Dino, Deh Manjhand',
        'Railway Colony, Kotri',
        'Deh Bholari, Taluka Thano Bula Khan',
        'Preetabad, Hyderabad',
        'Main Bazaar, Matiari town',
        'Hirabad, Hyderabad',
        'Saeedabad Town Committee, Ward 2',
        'Employees Colony, Kotri',
        'Sindh Housing Scheme Phase 2, Jamshoro',
        'Fisherfolk Colony, Keti Bunder',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = Carbon::instance($this->faker->dateTimeBetween('-45 days', '-1 day'));
        $status = $this->faker->randomElement(CaseStatus::cases());

        return [
            'case_number' => 'CASE-'.$createdAt->year.'-'.str_pad(
                (string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT
            ),
            'complainant_name' => $this->faker->name(),
            'complainant_cnic' => $this->faker->numerify('#####-#######-#'),
            'complainant_phone' => '03'.$this->faker->numberBetween(0, 4).'-'.$this->faker->numerify('#######'),
            'complainant_address' => 'House '.$this->faker->numberBetween(1, 240).', '
                .$this->faker->randomElement(self::AREAS),
            'issue_summary' => $this->faker->randomElement(self::ISSUES),
            'department_id' => Department::factory(),
            'created_by' => User::factory(),
            'assigned_to_user_id' => null,
            'priority' => $this->faker->randomElement(CasePriority::cases()),
            'status' => $status,
            'source' => CaseSource::Staff,
            'resolved_at' => $status === CaseStatus::Resolved ? $createdAt->copy()->addDays(3) : null,
            'closed_at' => $status === CaseStatus::Closed ? $createdAt->copy()->addDays(6) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function status(CaseStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'resolved_at' => $status === CaseStatus::Resolved ? Carbon::now()->subDays(2) : null,
            'closed_at' => $status === CaseStatus::Closed ? Carbon::now()->subDay() : null,
        ]);
    }

    public function priority(CasePriority $priority): static
    {
        return $this->state(fn (): array => ['priority' => $priority]);
    }

    public function forDepartment(Department $department): static
    {
        return $this->state(fn (): array => ['department_id' => $department->getKey()]);
    }

    public function loggedBy(User $user): static
    {
        return $this->state(fn (): array => ['created_by' => $user->getKey()]);
    }

    /**
     * Past the 15-day resolution window and still open — feeds the "Overdue"
     * stat card on the dashboard.
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => CaseStatus::Escalated,
            'priority' => CasePriority::High,
            'created_at' => Carbon::now()->subDays($this->faker->numberBetween(16, 40)),
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }
}
