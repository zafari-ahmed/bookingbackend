<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * `color_tag` holds a design-system token name, not a hex value, so the UI
     * resolves it to the same status/tag palette everything else uses.
     *
     * @var array<int, array{name: string, color_tag: string, focal_person: string, description: string}>
     */
    private const DEPARTMENTS = [
        [
            'name' => 'Child Protection',
            'color_tag' => 'protection',
            'focal_person' => 'Deputy Director, Child Protection Unit',
            'description' => 'Cases involving minors — child labour, custody, abuse and rehabilitation referrals.',
        ],
        [
            'name' => 'Health',
            'color_tag' => 'services',
            'focal_person' => 'District Health Officer',
            'description' => 'Rural health centres, ambulance availability, vaccination outreach and hospital grievances.',
        ],
        [
            'name' => 'Police',
            'color_tag' => 'enforcement',
            'focal_person' => 'SDPO, District Liaison',
            'description' => 'Law-and-order assistance, protection requests and enforcement support to other departments.',
        ],
        [
            'name' => 'Women Protection',
            'color_tag' => 'protection',
            'focal_person' => 'Incharge, Women Protection Cell',
            'description' => 'Harassment complaints, shelter admissions and protection orders.',
        ],
        [
            'name' => 'Social Welfare',
            'color_tag' => 'welfare',
            'focal_person' => 'Social Welfare Officer',
            'description' => 'Pensions, income support disbursement and welfare registration issues.',
        ],
        [
            'name' => 'Local Government',
            'color_tag' => 'civic',
            'focal_person' => 'Municipal Commissioner (Admin)',
            'description' => 'Sanitation, street lighting, encroachment, drainage and municipal licensing.',
        ],
        [
            'name' => 'Youth Affairs',
            'color_tag' => 'welfare',
            'focal_person' => 'Assistant Director, Youth Affairs',
            'description' => 'Skills stipends, youth centres and sports facility grievances.',
        ],
        [
            'name' => 'Education',
            'color_tag' => 'services',
            'focal_person' => 'Taluka Education Officer',
            'description' => 'Teacher absenteeism, school infrastructure and admission disputes.',
        ],
        [
            'name' => 'Disability Welfare',
            'color_tag' => 'welfare',
            'focal_person' => 'Assistant Director, Special Education',
            'description' => 'Disability certification, special education placement and accessibility complaints.',
        ],
    ];

    public function run(): void
    {
        foreach (self::DEPARTMENTS as $department) {
            Department::updateOrCreate(
                ['slug' => Str::slug($department['name'])],
                [
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'color_tag' => $department['color_tag'],
                    'focal_person' => 'Focal person: '.$department['focal_person'],
                    'is_active' => true,
                ],
            );
        }
    }
}
