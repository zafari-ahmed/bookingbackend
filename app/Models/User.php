<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $designation
 * @property UserRole $role
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property Collection<int, Department> $departments
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'designation',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Department ids resolved once per request — the dashboard, the case policy
     * and the global scope all need them and would otherwise re-query.
     *
     * @var array<int, int>|null
     */
    private ?array $accessibleDepartmentIdCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        // The pivot records only when access was granted, so `withTimestamps()`
        // (which insists on an updated_at column) is deliberately not used.
        return $this->belongsToMany(Department::class)
            ->withPivot('is_primary', 'created_at');
    }

    /**
     * @return HasMany<CaseModel, $this>
     */
    public function createdCases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'created_by');
    }

    /**
     * @return HasMany<CaseModel, $this>
     */
    public function assignedCases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'assigned_to_user_id');
    }

    /**
     * @return HasMany<CaseComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class);
    }

    /**
     * @return HasMany<CaseAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CaseAttachment::class, 'uploaded_by');
    }

    /**
     * @return HasMany<CaseActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(CaseActivityLog::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isDepartmentAdmin(): bool
    {
        return $this->role === UserRole::DepartmentAdmin;
    }

    public function requiresTwoFactor(): bool
    {
        return in_array(
            $this->role->value,
            (array) config('fortify.two_factor_required_roles', []),
            true
        );
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Every department this account may act within. A super_admin has no pivot
     * rows at all and is handled by the caller, never by this list.
     *
     * @return array<int, int>
     */
    public function accessibleDepartmentIds(): array
    {
        if ($this->accessibleDepartmentIdCache !== null) {
            return $this->accessibleDepartmentIdCache;
        }

        // Queried explicitly rather than through the relation accessor: this
        // runs inside the global scope, where a lazy load would both surprise
        // the caller and trip strict-mode lazy-loading protection.
        $ids = $this->relationLoaded('departments')
            ? $this->departments->pluck('id')
            : $this->departments()->pluck('departments.id');

        return $this->accessibleDepartmentIdCache = $ids
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    public function belongsToDepartment(int $departmentId): bool
    {
        return in_array($departmentId, $this->accessibleDepartmentIds(), true);
    }

    public function primaryDepartment(): ?Department
    {
        $this->loadMissing('departments');

        return $this->departments->firstWhere('pivot.is_primary', true)
            ?? $this->departments->first();
    }

    /**
     * The home-department picker only appears when the officer actually has
     * more than one assignment to choose from.
     */
    public function hasMultipleDepartments(): bool
    {
        return count($this->accessibleDepartmentIds()) > 1;
    }

    /**
     * Flip the primary flag without touching the rest of the officer's grants.
     */
    public function makePrimary(Department $department): void
    {
        $departmentId = (int) $department->getKey();

        if (! $this->belongsToDepartment($departmentId)) {
            abort(403, 'You can only set a department you already belong to as primary.');
        }

        $this->loadMissing('departments');

        foreach ($this->departments as $assigned) {
            $this->departments()->updateExistingPivot((int) $assigned->id, [
                'is_primary' => (int) $assigned->id === $departmentId,
            ]);
        }

        $this->unsetRelation('departments');
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->replaceMatches('/^(Dr|Mr|Mrs|Ms|SDPO|SIP|ASI|XEN|AEN)\.?\s+/i', '')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    public function contextLabel(): string
    {
        $department = $this->isSuperAdmin()
            ? 'AC Office'
            : ($this->primaryDepartment()?->name ?? 'Unassigned');

        return $department.' · '.$this->role->label();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
