<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('employer_id');
            $table->string('title');
            $table->integer('min_salary')->default(0);
            $table->integer('max_salary')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->jsonb('employment_types')->default('[]');
            $table->jsonb('workplaces')->default('[]');
            $table->jsonb('researcher_location_ids')->default('[]');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->foreign('employer_id')
                ->references('id')
                ->on('employers')
                ->onDelete('cascade');

            $table->index('employer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
