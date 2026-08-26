<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityPeriod;
use App\Enums\ActivityType;
use Database\Factories\CaseActivityLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Immutable audit trail entry. Written exclusively by observers so that no code
 * path can mutate a case without leaving a record.
 *
 * @property int $id
 * @property int $case_id
 * @property int|null $user_id
 * @property ActivityType $action_type
 * @property string $description
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class CaseActivityLog extends Model
{
    /** @use HasFactory<CaseActivityLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'case_id',
        'user_id',
        'action_type',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => ActivityType::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaseModel, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actorLabel(): string
    {
        return $this->user?->name ?? 'System';
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForPeriod(Builder $query, ActivityPeriod $period): Builder
    {
        $since = $period->since();

        return $since === null
            ? $query
            : $query->where('created_at', '>=', $since);
    }
}
