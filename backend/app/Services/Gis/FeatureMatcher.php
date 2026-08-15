<?php

namespace App\Services\Gis;

use App\Models\ExcelRow;
use App\Models\MapFeature;

class FeatureMatcher
{
    public function matchByKey(int $projectId, string $featureColumn, string $excelColumn): array
    {
        $matched = 0;
        $unmatched = 0;

        MapFeature::query()
            ->where('project_id', $projectId)
            ->chunkById(200, function ($features) use ($excelColumn, $featureColumn, &$matched, &$unmatched) {
                foreach ($features as $feature) {
                    $featureValue = $feature->{$featureColumn} ?? data_get($feature->properties, $featureColumn);

                    $row = ExcelRow::query()
                        ->where('match_key', $featureValue)
                        ->orWhere("data->{$excelColumn}", $featureValue)
                        ->first();

                    if ($row) {
                        $feature->forceFill(['excel_row_id' => $row->id])->save();
                        $matched++;
                    } else {
                        $unmatched++;
                    }
                }
            });

        return compact('matched', 'unmatched');
    }
}

