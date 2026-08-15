<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class MapFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'layer_id',
        'excel_row_id',
        'name',
        'external_id',
        'cadastre_number',
        'geometry_type',
        'properties',
        'status',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function excelRow(): BelongsTo
    {
        return $this->belongsTo(ExcelRow::class);
    }

    public function layer(): BelongsTo
    {
        return $this->belongsTo(Layer::class);
    }
}
