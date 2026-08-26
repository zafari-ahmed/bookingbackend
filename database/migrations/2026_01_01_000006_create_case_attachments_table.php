<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();

            // A file can outlive the remark it was posted with; keep the row.
            $table->foreignId('comment_id')->nullable()
                ->constrained('case_comments')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->timestamp('created_at')->nullable();

            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_attachments');
    }
};
