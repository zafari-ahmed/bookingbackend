<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            // The department the author is acting *as* — chosen from the
            // departments they actually hold access to on this case.
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->text('comment');
            $table->foreignId('forwarded_to_department_id')->nullable()
                ->constrained('departments')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_comments');
    }
};
