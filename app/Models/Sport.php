<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'icon', 'color', 'status', 'sort_order'])]
class Sport extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Sport $sport): void {
            if (blank($sport->slug)) {
                $sport->slug = Str::slug($sport->name);
            }
        });
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class)->orderBy('sort_order')->orderBy('name');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RecordStatus::Active->value);
    }
}
