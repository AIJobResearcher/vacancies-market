<?php

namespace App\Infrastructure\Models;

use Database\Factories\InterviewerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interviewer extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return InterviewerFactory::new();
    }
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'employer_id',
        'full_name',
        'position',
        'email',
        'phone',
        'portal_id',
        'profile_url',
        'vacancy_ids',
    ];

    protected $casts = [
        'vacancy_ids' => 'json',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }
}
