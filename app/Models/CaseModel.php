<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CasePriority;
use App\Enums\CaseSource;
use App\Enums\CaseStatus;
use App\Models\Scopes\DepartmentScope;
use App\Observers\CaseObserver;
use Database\Factories\CaseModelFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A logged public grievance. Named `CaseModel` because `Case` is a reserved
 * word in PHP; the underlying table is still `cases`.
 *
 * @property int $id
 * @property string $case_number
 * @property string $complainant_name
 * @property string|null $complainant_cnic
 * @property string|null $complainant_phone
 * @property string|null $complainant_address
 * @property string $issue_summary
 * @property int $department_id
 * @property int $created_by
 * @property int|null $assigned_to_user_id
 * @property CasePriority $priority
 * @property CaseStatus $status
 * @property CaseSource $source
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property Carbon $created_at
 */
#[ObservedBy(CaseObserver::class)]
#[ScopedBy(DepartmentScope::class)]
class CaseModel extends Model
{
    /** @use HasFactory<CaseModelFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'complainant_name',
        'complainant_cnic',
        'complainant_phone',
        'complainant_address',
        'issue_summary',
        'department_id',
        'created_by',
        'assigned_to_user_id',
        'priority',
        'status',
        'source',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => CasePriority::class,
            'status' => CaseStatus::class,
            'source' => CaseSource::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'case_number';
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * @return HasMany<CaseRouting, $this>
     */
    public function routingSteps(): HasMany
    {
        return $this->hasMany(CaseRouting::class, 'case_id')->oldest('created_at');
    }

    /**
     * @return HasMany<CaseComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class, 'case_id')->oldest();
    }

    /**
     * @return HasMany<CaseAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CaseAttachment::class, 'case_id')->latest('created_at');
    }

    /**
     * @return HasMany<CaseActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(CaseActivityLog::class, 'case_id')->latest('created_at');
    }

    /**
     * Every department that has ever held this case — used to decide who gets
     * notified and which departments a multi-department user may comment as.
     *
     * @return array<int, int>
     */
    public function involvedDepartmentIds(): array
    {
        return collect([$this->department_id])
            ->merge($this->routingSteps()->pluck('department_id'))
            ->unique()
            ->values()
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    public function daysOpen(): int
    {
        $end = $this->resolved_at ?? $this->closed_at ?? Carbon::now();

        return (int) $this->created_at->startOfDay()->diffInDays($end->startOfDay());
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->daysOpen() >= self::overdueAfterDays();
    }

    /**
     * The AC office's service standard: an open case older than this is
     * reported as overdue on the dashboard and in the register.
     */
    public static function overdueAfterDays(): int
    {
        return (int) config('cases.overdue_after_days', 15);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_column(CaseStatus::openStatuses(), 'value'));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->where(
            'created_at',
            '<=',
            Carbon::now()->subDays(self::overdueAfterDays())
        );
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [CasePriority::High->value, CasePriority::Urgent->value]);
    }

    /**
     * Global search across the fields the top bar advertises: complainant name,
     * CNIC and case number, plus the issue text.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $inner) use ($like): void {
            $inner
                ->where('case_number', 'like', $like)
                ->orWhere('complainant_name', 'like', $like)
                ->orWhere('complainant_cnic', 'like', $like)
                ->orWhere('complainant_phone', 'like', $like)
                ->orWhere('issue_summary', 'like', $like);
        });
    }
}
