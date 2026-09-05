<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviewers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('employer_id');
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->json('profile_urls')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('employer_id')
                ->references('id')
                ->on('employers')
                ->onDelete('cascade');

            $table->index('employer_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviewers');
    }
};
