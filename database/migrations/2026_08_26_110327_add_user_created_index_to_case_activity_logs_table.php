<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The personal activity log lists a user's own rows newest-first, optionally
     * bounded by created_at. The existing indexes are on case_id and action_type.
     */
    public function up(): void
    {
        Schema::table('case_activity_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('case_activity_logs', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
