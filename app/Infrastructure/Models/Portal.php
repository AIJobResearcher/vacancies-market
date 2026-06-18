<?php

namespace App\Infrastructure\Models;

use Database\Factories\PortalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portal extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return PortalFactory::new();
    }
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'base_url',
        'api_endpoint',
        'parsing_config',
        'crawl_delay_seconds',
    ];

    protected $casts = [
        'parsing_config' => 'json',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
