<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviewers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employer_id');
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('portal_id')->nullable();
            $table->string('profile_url')->nullable();
            $table->json('vacancy_ids')->default(json_encode([]));
            $table->timestamps();
            $table->foreign('employer_id')->references('id')->on('employers')->onDelete('cascade');
            $table->index('employer_id');
            $table->index('portal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviewers');
    }
};
