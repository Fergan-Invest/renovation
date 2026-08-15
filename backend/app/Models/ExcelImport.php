<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcelImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'original_name',
        'stored_path',
        'status',
        'summary',
    ];

    protected $casts = [
        'summary' => 'array',
    ];
}

