<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CaseAttachmentObserver;
use Database\Factories\CaseAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $case_id
 * @property int|null $comment_id
 * @property int $uploaded_by
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size
 * @property Carbon $created_at
 */
#[ObservedBy(CaseAttachmentObserver::class)]
class CaseAttachment extends Model
{
    /** @use HasFactory<CaseAttachmentFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'case_id',
        'comment_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
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
     * @return BelongsTo<CaseComment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(CaseComment::class, 'comment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extensionLabel(): string
    {
        return Str::upper(Str::afterLast($this->file_name, '.')) ?: 'FILE';
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->file_size, precision: 1);
    }
}
