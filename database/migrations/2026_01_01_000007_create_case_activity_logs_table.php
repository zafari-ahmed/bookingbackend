<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();

            // Null for system-generated events (scheduled escalation, imports).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 50);
            $table->text('description');
            $table->json('meta')->nullable();

            // Immutable audit trail: no updated_at.
            $table->timestamp('created_at')->nullable();

            $table->index('case_id');
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_activity_logs');
    }
};
