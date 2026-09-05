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
            $table->text('description')->nullable();
            $table->integer('salary_min')->default(0);
            $table->integer('salary_max')->nullable();
            $table->string('salary_currency', 3)->default('USD');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->enum('employment_type', [
                'part-time',
                'contract',
                'internship',
                'full-time',
                'volunteer',
            ]);
            $table->enum('workplace', ['remote', 'on-site', 'hybrid']);
            $table->timestamp('posted_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('external_urls');
            $table->string('internal_url')->nullable();
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
