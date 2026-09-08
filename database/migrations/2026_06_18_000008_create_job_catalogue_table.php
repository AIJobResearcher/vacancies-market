<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named "job_catalogue": Laravel reserves the "jobs" table for its queue.
        Schema::create('job_catalogue', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->uuid('parent_job_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->index('parent_job_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_catalogue');
    }
};
