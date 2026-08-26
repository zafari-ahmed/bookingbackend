<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoutingAction;
use Database\Factories\CaseRoutingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One hand-off in a case's journey between departments. Append-only.
 *
 * @property int $id
 * @property int $case_id
 * @property int $department_id
 * @property int $assigned_by
 * @property int|null $assigned_to_user_id
 * @property RoutingAction $action
 * @property string|null $notes
 * @property Carbon $created_at
 */
class CaseRouting extends Model
{
    /** @use HasFactory<CaseRoutingFactory> */
    use HasFactory;

    protected $table = 'case_routing';

    public const UPDATED_AT = null;

    protected $fillable = [
        'case_id',
        'department_id',
        'assigned_by',
        'assigned_to_user_id',
        'action',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action' => RoutingAction::class,
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
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
