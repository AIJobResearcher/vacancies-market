<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        });

        // Case-insensitive uniqueness of the dictionary title (5.1.1); a plain
        // unique index would treat "PHP" and "php" as different requirements.
        DB::statement('CREATE UNIQUE INDEX requirements_title_lower_unique ON requirements (LOWER(title))');
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
