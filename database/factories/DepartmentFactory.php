<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true).' Department';

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(12),
            'color_tag' => $this->faker->randomElement([
                'protection', 'services', 'enforcement', 'welfare', 'civic',
            ]),
            'focal_person' => 'Focal person: '.$this->faker->jobTitle(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
