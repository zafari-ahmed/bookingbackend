<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['court_id', 'block_date', 'start_time', 'end_time', 'reason', 'status', 'created_by'])]
class CourtBlock extends Model
{
    protected function casts(): array
    {
        return [
            'block_date' => 'date',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
