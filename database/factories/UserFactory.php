<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Hashing once keeps large seeds fast — bcrypt at 12 rounds is deliberately
     * slow and 20 users would otherwise cost several seconds.
     */
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name' => $name,
            'email' => Str::slug($name, '.').'@'.$this->faker->numberBetween(1, 9999).'.sindh.gov.pk',
            'email_verified_at' => Carbon::now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => '03'.$this->faker->numberBetween(0, 4).'-'.$this->faker->numerify('#######'),
            'designation' => $this->faker->jobTitle(),
            'role' => UserRole::DepartmentUser,
            'is_active' => true,
            'last_login_at' => $this->faker->optional(0.8)->dateTimeBetween('-10 days'),
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::SuperAdmin]);
    }

    public function departmentAdmin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::DepartmentAdmin]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    /**
     * Attach the user to one or more departments, marking the first as primary.
     *
     * @param  iterable<int, Department>|Department  $departments
     */
    public function inDepartments(iterable|Department $departments): static
    {
        $departments = $departments instanceof Department ? [$departments] : $departments;

        return $this->afterCreating(function (User $user) use ($departments): void {
            foreach ($departments as $index => $department) {
                $user->departments()->attach($department, [
                    'is_primary' => $index === 0,
                    'created_at' => Carbon::now(),
                ]);
            }

            $user->load('departments');
        });
    }
}
