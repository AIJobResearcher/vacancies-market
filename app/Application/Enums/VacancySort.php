<?php

declare(strict_types=1);

namespace App\Application\Enums;

enum VacancySort: string
{
    case Relevance = 'relevance';
    case Date = 'date';
    case SalaryAsc = 'salary_asc';
    case SalaryDesc = 'salary_desc';
}
