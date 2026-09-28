<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('source_id');
            $table->string('type');
            $table->text('value');

            $table->foreign('source_id')
                ->references('id')
                ->on('sources')
                ->onDelete('restrict');

            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
