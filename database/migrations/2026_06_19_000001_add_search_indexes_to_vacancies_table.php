<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->index(['status', 'country', 'city'], 'vacancies_status_country_city_index');
            $table->index('country', 'vacancies_country_index');
            $table->index('city', 'vacancies_city_index');
            $table->index('salary_min', 'vacancies_salary_min_index');
            $table->index('salary_max', 'vacancies_salary_max_index');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropIndex('vacancies_status_country_city_index');
            $table->dropIndex('vacancies_country_index');
            $table->dropIndex('vacancies_city_index');
            $table->dropIndex('vacancies_salary_min_index');
            $table->dropIndex('vacancies_salary_max_index');
        });
    }
};
