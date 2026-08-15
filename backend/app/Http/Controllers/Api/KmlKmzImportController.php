<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Gis\KmlKmzParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KmlKmzImportController extends Controller
{
    public function store(Request $request, KmlKmzParser $parser): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $file = $data['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['kml', 'kmz'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Faqat KML yoki KMZ fayl yuklash mumkin.',
            ]);
        }

        $path = $file->storeAs('imports/kml-kmz', Str::uuid().'.'.$extension);
        $fullPath = Storage::path($path);
        $kml = $parser->extractKml($fullPath);
        $placemarks = $parser->placemarks($kml);

        return response()->json([
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'placemark_count' => count($placemarks),
            'preview' => array_slice(array_map(fn (array $placemark) => [
                'name' => $placemark['name'],
                'description' => $placemark['description'],
                'properties' => $placemark['properties'],
            ], $placemarks), 0, 10),
        ], 201);
    }
}
