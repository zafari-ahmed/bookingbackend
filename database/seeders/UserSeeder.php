<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    /**
     * Officers keyed by the department they primarily serve. Two entries carry
     * an `also` list, which produces the multi-department pivot rows that prove
     * the many-to-many access model works end to end.
     *
     * @var array<string, array<int, array{name: string, designation: string, role: string, also?: array<int, string>, active?: bool}>>
     */
    private const OFFICERS = [
        'Child Protection' => [
            ['name' => 'Rukhsana Memon', 'designation' => 'Deputy Director', 'role' => 'department_admin'],
            ['name' => 'Waqar Ahmed Shar', 'designation' => 'Protection Officer', 'role' => 'department_user'],
            ['name' => 'Sana Qureshi', 'designation' => 'Case Worker', 'role' => 'department_user', 'also' => ['Women Protection']],
        ],
        'Health' => [
            ['name' => 'Aftab Hussain', 'designation' => 'District Health Officer', 'role' => 'department_admin'],
            ['name' => 'Shaista Panhwar', 'designation' => 'Medical Superintendent', 'role' => 'department_user'],
            ['name' => 'Yasir Lashari', 'designation' => 'Health Coordinator', 'role' => 'department_user'],
        ],
        'Police' => [
            ['name' => 'Rafiq Ahmed', 'designation' => 'Sub-Divisional Police Officer', 'role' => 'department_admin'],
            ['name' => 'Nasir Jokhio', 'designation' => 'Station Investigation Officer', 'role' => 'department_user'],
            ['name' => 'Ghulam Ali Chandio', 'designation' => 'Complaint Cell Incharge', 'role' => 'department_user'],
        ],
        'Women Protection' => [
            ['name' => 'Nazia Talpur', 'designation' => 'Incharge, Protection Cell', 'role' => 'department_user'],
            ['name' => 'Farah Siddiqui', 'designation' => 'Legal Aid Officer', 'role' => 'department_user'],
        ],
        'Social Welfare' => [
            ['name' => 'Imtiaz Bhutto', 'designation' => 'Social Welfare Officer', 'role' => 'department_user'],
            ['name' => 'Kausar Bibi', 'designation' => 'Field Supervisor', 'role' => 'department_user', 'active' => false],
            ['name' => 'Zohaib Khaskheli', 'designation' => 'Registration Clerk', 'role' => 'department_user', 'also' => ['Disability Welfare', 'Youth Affairs']],
        ],
        'Local Government' => [
            ['name' => 'Aslam Soomro', 'designation' => 'Municipal Commissioner (Admin)', 'role' => 'department_user'],
            ['name' => 'Naveed Mangi', 'designation' => 'Sanitary Inspector', 'role' => 'department_user'],
            ['name' => 'Hina Junejo', 'designation' => 'Licence Branch Officer', 'role' => 'department_user'],
        ],
        'Youth Affairs' => [
            ['name' => 'Bilal Abro', 'designation' => 'Assistant Director', 'role' => 'department_user'],
        ],
        'Education' => [
            ['name' => 'Shabana Rajput', 'designation' => 'Taluka Education Officer', 'role' => 'department_user'],
            ['name' => 'Mehran Solangi', 'designation' => 'Monitoring Officer', 'role' => 'department_user'],
        ],
        'Disability Welfare' => [
            ['name' => 'Adeel Baloch', 'designation' => 'Assistant Director, Special Education', 'role' => 'department_user'],
            ['name' => 'Rabia Shaikh', 'designation' => 'Assessment Coordinator', 'role' => 'department_user'],
        ],
    ];

    public function run(): void
    {
        $departments = Department::all()->keyBy('name');
        $password = Hash::make(self::DEMO_PASSWORD);

        // The AC office account: no pivot rows at all, sees every department.
        User::updateOrCreate(
            ['email' => 'ac.office@sindh.gov.pk'],
            [
                'name' => 'Shazia Junejo',
                'designation' => 'Assistant Commissioner',
                'password' => $password,
                'phone' => '0300-2211004',
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => Carbon::now(),
                'last_login_at' => Carbon::now()->subHours(3),
            ],
        );

        foreach (self::OFFICERS as $departmentName => $officers) {
            $department = $departments->get($departmentName);

            if ($department === null) {
                continue;
            }

            foreach ($officers as $officer) {
                $user = User::updateOrCreate(
                    ['email' => $this->emailFor($officer['name'], $departmentName)],
                    [
                        'name' => $officer['name'],
                        'designation' => $officer['designation'],
                        'password' => $password,
                        'phone' => '03'.random_int(0, 4).'-'.random_int(1000000, 9999999),
                        'role' => UserRole::from($officer['role']),
                        'is_active' => $officer['active'] ?? true,
                        'email_verified_at' => Carbon::now(),
                        'last_login_at' => Carbon::now()->subDays(random_int(0, 12)),
                    ],
                );

                $assignments = [$departmentName, ...($officer['also'] ?? [])];

                $user->departments()->sync(
                    collect($assignments)
                        ->map(fn (string $name): ?Department => $departments->get($name))
                        ->filter()
                        ->mapWithKeys(fn (Department $dept, int $index): array => [
                            $dept->getKey() => [
                                'is_primary' => $index === 0,
                                'created_at' => Carbon::now(),
                            ],
                        ])
                        ->all()
                );
            }
        }
    }

    private function emailFor(string $name, string $department): string
    {
        $local = str($name)->lower()->replace(' ', '.')->toString();
        $domain = str($department)->lower()->replace(' ', '')->toString();

        return $local.'@'.$domain.'.sindh.gov.pk';
    }
}
