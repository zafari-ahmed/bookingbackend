<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }

    public function canManageFacilities(): bool
    {
        return $this->isAdmin();
    }

    public function canViewReports(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function canCancelBookings(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function canManageHolds(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Manager], true);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : explode(',', $roles);

        return in_array($this->role?->value, array_map('trim', $roles), true);
    }
}
