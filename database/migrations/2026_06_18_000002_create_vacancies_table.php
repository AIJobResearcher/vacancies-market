<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employer_id');
            $table->string('title');
            $table->text('description');
            $table->json('requirements')->default(json_encode([]));
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->string('salary_currency')->default('USD');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->foreign('employer_id')->references('id')->on('employers')->onDelete('cascade');
            $table->index('employer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
