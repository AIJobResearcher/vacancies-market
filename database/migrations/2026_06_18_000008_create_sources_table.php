<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('vacancy_id');
            $table->uuid('portal_id');
            $table->string('external_vacancy_id')->nullable();
            $table->string('external_url');
            $table->string('title');
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->foreign('vacancy_id')
                ->references('id')
                ->on('vacancies')
                ->onDelete('cascade');

            $table->foreign('portal_id')
                ->references('id')
                ->on('portals')
                ->onDelete('restrict');

            $table->unique(['vacancy_id', 'external_url']);
            $table->index('vacancy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
