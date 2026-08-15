<?php

namespace App\Services\Gis;

use RuntimeException;
use ZipArchive;

class KmlKmzParser
{
    public function extractKml(string $path): string
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'kml') {
            return file_get_contents($path) ?: '';
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('KMZ faylni ochib bo‘lmadi.');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if ($name && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'kml') {
                $content = $zip->getFromIndex($index);
                $zip->close();

                return $content ?: '';
            }
        }

        $zip->close();
        throw new RuntimeException('KMZ ichidan KML topilmadi.');
    }

    public function placemarks(string $kml): array
    {
        $xml = simplexml_load_string($kml);
        if (! $xml) {
            throw new RuntimeException('KML XML formatida xatolik bor.');
        }

        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        $placemarks = $xml->xpath('//kml:Placemark') ?: [];

        return array_map(function ($placemark) {
            return [
                'name' => trim((string) $placemark->name) ?: null,
                'description' => trim((string) $placemark->description) ?: null,
                'properties' => $this->extendedData($placemark),
                'geometry' => $this->geometry($placemark),
                'raw_xml' => $placemark->asXML(),
            ];
        }, $placemarks);
    }

    public function featureCollection(string $kml, array $baseProperties = []): array
    {
        $features = array_values(array_filter(array_map(function (array $placemark) use ($baseProperties) {
            if (! $placemark['geometry']) {
                return null;
            }

            return [
                'type' => 'Feature',
                'geometry' => $placemark['geometry'],
                'properties' => array_merge($baseProperties, [
                    'name' => $placemark['name'],
                    'description' => $placemark['description'],
                    'data' => $placemark['properties'],
                ]),
            ];
        }, $this->placemarks($kml))));

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function extendedData($placemark): array
    {
        $properties = [];

        foreach ($placemark->ExtendedData->Data ?? [] as $data) {
            $attributes = $data->attributes();
            $name = isset($attributes['name']) ? (string) $attributes['name'] : null;
            if ($name) {
                $properties[$name] = (string) $data->value;
            }
        }

        return $properties;
    }

    private function geometry($placemark): ?array
    {
        $placemark->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');

        $point = $placemark->xpath('.//kml:Point/kml:coordinates')[0] ?? null;
        if ($point) {
            $coordinates = $this->parseCoordinateLine((string) $point);

            return $coordinates ? [
                'type' => 'Point',
                'coordinates' => $coordinates[0],
            ] : null;
        }

        $lineString = $placemark->xpath('.//kml:LineString/kml:coordinates')[0] ?? null;
        if ($lineString) {
            $coordinates = $this->parseCoordinateLine((string) $lineString);

            return count($coordinates) > 1 ? [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ] : null;
        }

        $polygon = $placemark->xpath('.//kml:Polygon/kml:outerBoundaryIs/kml:LinearRing/kml:coordinates')[0] ?? null;
        if ($polygon) {
            $coordinates = $this->parseCoordinateLine((string) $polygon);

            return count($coordinates) > 3 ? [
                'type' => 'Polygon',
                'coordinates' => [$coordinates],
            ] : null;
        }

        return null;
    }

    private function parseCoordinateLine(string $value): array
    {
        $tuples = preg_split('/\s+/', trim($value)) ?: [];
        $coordinates = [];

        foreach ($tuples as $tuple) {
            $parts = array_map('trim', explode(',', $tuple));
            if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                continue;
            }

            $coordinates[] = [(float) $parts[0], (float) $parts[1]];
        }

        return $coordinates;
    }
}
