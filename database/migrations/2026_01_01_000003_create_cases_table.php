<?php

declare(strict_types=1);

use App\Enums\CasePriority;
use App\Enums\CaseSource;
use App\Enums\CaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table): void {
            $table->id();
            $table->string('case_number', 30)->unique();
            $table->string('complainant_name', 150);
            $table->string('complainant_cnic', 20)->nullable();
            $table->string('complainant_phone', 20)->nullable();
            $table->text('complainant_address')->nullable();
            $table->text('issue_summary');
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('priority', array_column(CasePriority::cases(), 'value'))
                ->default(CasePriority::Normal->value);
            $table->enum('status', array_column(CaseStatus::cases(), 'value'))
                ->default(CaseStatus::Pending->value);
            $table->enum('source', array_column(CaseSource::cases(), 'value'))
                ->default(CaseSource::Staff->value);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Exact filter combination the dashboard runs on every page load.
            $table->index(['department_id', 'status']);
            $table->index('status');
            $table->index('priority');
            $table->index('complainant_cnic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
