<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layer extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'source_file',
        'style',
        'is_visible',
    ];

    protected $casts = [
        'style' => 'array',
        'is_visible' => 'boolean',
    ];
}

