<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_job_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_id');
            $table->uuid('job_id');
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->unsignedTinyInteger('relevance_score')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->foreign('vacancy_id')
                ->references('id')
                ->on('vacancies')
                ->onDelete('cascade');

            $table->index('job_id');
            $table->index('unassigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_job_assignments');
    }
};
