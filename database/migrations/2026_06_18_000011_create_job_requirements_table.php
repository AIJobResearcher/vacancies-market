<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_requirements', function (Blueprint $table): void {
            $table->uuid('job_id');
            $table->uuid('requirement_id');

            $table->foreign('job_id')
                ->references('id')
                ->on('job_catalogue')
                ->onDelete('cascade');

            $table->foreign('requirement_id')
                ->references('id')
                ->on('requirements')
                ->onDelete('cascade');

            $table->primary(['job_id', 'requirement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_requirements');
    }
};
