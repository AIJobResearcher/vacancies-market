<?php

namespace App\Infrastructure\Models;

use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacancy extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return VacancyFactory::new();
    }
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'employer_id',
        'title',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'salary_currency',
        'status',
        'country',
        'city',
        'version',
    ];

    protected $casts = [
        'requirements' => 'json',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }
}
