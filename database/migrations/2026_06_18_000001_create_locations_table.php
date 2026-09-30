<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('iso_name', 5)->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->enum('type', ['country', 'city', 'unification-of-countries', 'region']);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
