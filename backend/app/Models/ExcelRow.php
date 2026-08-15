<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcelRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'excel_import_id',
        'row_number',
        'match_key',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}

