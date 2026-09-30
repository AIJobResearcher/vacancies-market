<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_requirement_assignments', function (Blueprint $table): void {
            $table->uuid('vacancy_id');
            $table->uuid('requirement_id');

            $table->foreign('vacancy_id')
                ->references('id')
                ->on('vacancies')
                ->onDelete('cascade');

            $table->foreign('requirement_id')
                ->references('id')
                ->on('requirements')
                ->onDelete('restrict');

            $table->index('requirement_id');
            $table->primary(['vacancy_id', 'requirement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_requirement_assignments');
    }
};
