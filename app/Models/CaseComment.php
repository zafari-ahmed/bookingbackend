<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CaseCommentObserver;
use Database\Factories\CaseCommentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A remark on the case file. Soft-deleted only — an official remark is never
 * removed from the record.
 *
 * @property int $id
 * @property int $case_id
 * @property int $user_id
 * @property int $department_id
 * @property string $comment
 * @property int|null $forwarded_to_department_id
 */
#[ObservedBy(CaseCommentObserver::class)]
class CaseComment extends Model
{
    /** @use HasFactory<CaseCommentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'case_id',
        'user_id',
        'department_id',
        'comment',
        'forwarded_to_department_id',
    ];

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

    /**
     * The department the author was acting as when they wrote the remark.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function forwardedToDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'forwarded_to_department_id');
    }

    /**
     * @return HasMany<CaseAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CaseAttachment::class, 'comment_id');
    }
}
