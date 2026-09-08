<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_id');
            $table->string('source_key');
            $table->string('external_vacancy_id');
            $table->string('external_url');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_primary')->default(false);

            $table->foreign('vacancy_id')
                ->references('id')
                ->on('vacancies')
                ->onDelete('cascade');

            $table->unique(['source_key', 'external_vacancy_id']);
            $table->index('vacancy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_sources');
    }
};
