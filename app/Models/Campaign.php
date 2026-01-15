<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'pmov2.campaigns';

    protected $fillable = [
        'title',
        'badge',
        'description',
        'image',
        'start_date',
        'end_date',
        'status',
        'full_description',
        'terms_and_conditions',
        'parts_included',
        'rewards',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'parts_included' => 'array',
        'rewards' => 'array',
    ];
}
