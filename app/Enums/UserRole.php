<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case DepartmentAdmin = 'department_admin';
    case DepartmentUser = 'department_user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::DepartmentAdmin => 'Department Admin',
            self::DepartmentUser => 'Department User',
        };
    }

    /**
     * Tailwind classes for the role pill in Department & User Management.
     */
    public function pillClasses(): string
    {
        return match ($this) {
            self::SuperAdmin, self::DepartmentAdmin => 'bg-teal-tint text-teal',
            self::DepartmentUser => 'bg-closed-bg text-text-muted',
        };
    }

    public function isAdministrative(): bool
    {
        return $this === self::SuperAdmin || $this === self::DepartmentAdmin;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
