<?php

namespace App\Infrastructure\Models;

use Database\Factories\EmployerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return EmployerFactory::new();
    }
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'website',
        'email',
        'phone',
        'portal_id',
    ];

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    public function interviewers(): HasMany
    {
        return $this->hasMany(Interviewer::class);
    }
}
