<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapFeatureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bbox' => ['required', 'string'],
            'zoom' => ['nullable', 'integer', 'min:1', 'max:24'],
            'layers' => ['nullable', 'string'],
        ]);

        [$west, $south, $east, $north] = array_map('floatval', explode(',', $data['bbox']));
        $geometryColumn = ($data['zoom'] ?? 14) < 13 ? 'geometry_simplified' : 'geometry';
        $bboxWkt = sprintf(
            'POLYGON((%F %F,%F %F,%F %F,%F %F,%F %F))',
            $west,
            $south,
            $east,
            $south,
            $east,
            $north,
            $west,
            $north,
            $west,
            $south
        );

        $query = MapFeature::query()
            ->select([
                'id',
                'layer_id',
                'name',
                'external_id',
                'cadastre_number',
                'geometry_type',
                'properties',
            ])
            ->selectRaw("ST_AsGeoJSON(COALESCE($geometryColumn, geometry)) as geojson")
            ->whereRaw('MBRIntersects(geometry, ST_GeomFromText(?, 4326))', [$bboxWkt])
            ->limit(5000);

        if (! empty($data['layers'])) {
            $query->whereIn('layer_id', array_filter(array_map('intval', explode(',', $data['layers']))));
        }

        $features = $query->get()->map(fn (MapFeature $feature) => [
            'type' => 'Feature',
            'id' => $feature->id,
            'geometry' => json_decode($feature->geojson, true),
            'properties' => [
                'layer_id' => $feature->layer_id,
                'name' => $feature->name,
                'external_id' => $feature->external_id,
                'cadastre_number' => $feature->cadastre_number,
                'geometry_type' => $feature->geometry_type,
                'data' => $feature->properties,
            ],
        ]);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function show(MapFeature $feature): JsonResponse
    {
        $feature->load('excelRow');

        return response()->json($feature);
    }
}

