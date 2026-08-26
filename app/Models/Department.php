<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $color_tag
 * @property string|null $focal_person
 * @property bool $is_active
 */
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color_tag',
        'focal_person',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $department): void {
            if (blank($department->slug)) {
                $department->slug = Str::slug($department->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary', 'created_at');
    }

    /**
     * @return HasMany<CaseModel, $this>
     */
    public function cases(): HasMany
    {
        return $this->hasMany(CaseModel::class);
    }

    /**
     * @return HasMany<CaseRouting, $this>
     */
    public function routingSteps(): HasMany
    {
        return $this->hasMany(CaseRouting::class);
    }

    /**
     * @return HasMany<CaseComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Tailwind classes for the department tag pill. `color_tag` stores a design
     * system token name rather than a raw hex so Blade never inlines colours.
     */
    public function tagClasses(): string
    {
        return match ($this->color_tag) {
            'protection' => 'bg-progress-bg text-progress-text',
            'services' => 'bg-referred-bg text-referred-text',
            'enforcement' => 'bg-escalated-bg text-escalated-text',
            'welfare' => 'bg-resolved-bg text-resolved-text',
            'civic' => 'bg-pending-bg text-pending-text',
            default => 'bg-teal-tint text-teal',
        };
    }

    public function accentClass(): string
    {
        return match ($this->color_tag) {
            'protection' => 'border-l-progress-text',
            'services' => 'border-l-referred-text',
            'enforcement' => 'border-l-escalated-text',
            'welfare' => 'border-l-resolved-text',
            'civic' => 'border-l-pending-text',
            default => 'border-l-teal',
        };
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
