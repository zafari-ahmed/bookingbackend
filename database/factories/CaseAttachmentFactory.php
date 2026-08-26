<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CaseAttachment;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CaseAttachment>
 */
class CaseAttachmentFactory extends Factory
{
    protected $model = CaseAttachment::class;

    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const FILES = [
        ['joint-application-signed.pdf', 'application/pdf'],
        ['site-inspection-photo.jpg', 'image/jpeg'],
        ['notice-under-section-5.pdf', 'application/pdf'],
        ['complainant-cnic-scan.png', 'image/png'],
        ['departmental-compliance-report.pdf', 'application/pdf'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$name, $mime] = $this->faker->randomElement(self::FILES);

        return [
            'case_id' => CaseModel::factory(),
            'comment_id' => null,
            'uploaded_by' => User::factory(),
            'file_name' => $name,
            'file_path' => 'case-attachments/seed/'.Str::uuid()->toString().'.'.Str::afterLast($name, '.'),
            'file_type' => $mime,
            'file_size' => $this->faker->numberBetween(120_000, 4_000_000),
        ];
    }
}
