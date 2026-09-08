<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('base_url');
            $table->string('api_endpoint')->nullable();
            $table->unsignedInteger('crawl_delay_seconds')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portals');
    }
};
