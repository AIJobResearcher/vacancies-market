<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();

            $table->unique('title');
        });

        Schema::table('vacancy_requirement_assignments', function (Blueprint $table): void {
            $table->foreign('requirement_id')
                ->references('id')
                ->on('requirements')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('vacancy_requirement_assignments', function (Blueprint $table): void {
            $table->dropForeign(['requirement_id']);
        });

        Schema::dropIfExists('requirements');
    }
};
