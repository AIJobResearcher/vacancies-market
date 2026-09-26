<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviewer_vacancy_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('interviewer_id');
            $table->uuid('vacancy_id');
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->foreign('interviewer_id')
                ->references('id')
                ->on('interviewers')
                ->onDelete('cascade');

            $table->foreign('vacancy_id')
                ->references('id')
                ->on('vacancies')
                ->onDelete('cascade');

            $table->index('vacancy_id');
            $table->index('unassigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviewer_vacancy_assignments');
    }
};
