<?php

declare(strict_types=1);

use App\Enums\RoutingAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_routing', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', array_column(RoutingAction::cases(), 'value'));
            $table->text('notes')->nullable();

            // Immutable hand-off log: no updated_at.
            $table->timestamp('created_at')->nullable();

            $table->index('case_id');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_routing');
    }
};
