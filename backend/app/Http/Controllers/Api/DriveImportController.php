<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Gis\KmlKmzParser;
use Illuminate\Http\JsonResponse;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class DriveImportController extends Controller
{
    public function index(KmlKmzParser $parser): JsonResponse
    {
        $root = base_path('../data/drive-renovatsiya');
        $files = [];
        $features = [];

        if (! is_dir($root)) {
            return response()->json([
                'files' => [],
                'feature_collection' => [
                    'type' => 'FeatureCollection',
                    'features' => [],
                ],
                'message' => 'Fayllar hali data/drive-renovatsiya papkasiga yuklanmagan.',
            ]);
        }

        foreach ($this->localFiles($root) as $file) {
            $extension = strtolower($file->getExtension());
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

            if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                $files[] = $this->fileRecord($relativePath, 'excel', $file->getSize());
                continue;
            }

            if (in_array($extension, ['kml', 'kmz'], true)) {
                $record = $this->fileRecord($relativePath, $extension, $file->getSize());
                $collection = $parser->featureCollection($parser->extractKml($file->getPathname()), [
                    'source_file' => $relativePath,
                    'district' => $this->districtFromPath($relativePath),
                ]);
                $record['placemark_count'] = count($collection['features']);
                $files[] = $record;
                array_push($features, ...$collection['features']);
                continue;
            }

            if ($extension === 'zip') {
                $zipResult = $this->readZip($file, $relativePath, $parser);
                array_push($files, ...$zipResult['files']);
                array_push($features, ...$zipResult['features']);
            }
        }

        return response()->json([
            'files' => $files,
            'feature_collection' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
            'summary' => [
                'file_count' => count($files),
                'feature_count' => count($features),
                'excel_count' => count(array_filter($files, fn (array $file) => $file['type'] === 'excel')),
                'spatial_count' => count(array_filter($files, fn (array $file) => in_array($file['type'], ['kml', 'kmz'], true))),
            ],
        ]);
    }

    /**
     * @return array<int, SplFileInfo>
     */
    private function localFiles(string $root): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $files = [];

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function readZip(SplFileInfo $file, string $relativePath, KmlKmzParser $parser): array
    {
        $zip = new ZipArchive();
        $files = [$this->fileRecord($relativePath, 'zip', $file->getSize())];
        $features = [];

        if ($zip->open($file->getPathname()) !== true) {
            return ['files' => $files, 'features' => $features];
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if (! $entry || str_ends_with($entry, '/')) {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                $files[] = $this->fileRecord($relativePath.'/'.$entry, 'excel', $zip->statIndex($index)['size'] ?? 0);
                continue;
            }

            if (! in_array($extension, ['kml', 'kmz'], true)) {
                continue;
            }

            $content = $zip->getFromIndex($index);
            if ($content === false) {
                continue;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'drive-kmz-');
            file_put_contents($tempPath, $content);
            $kml = $extension === 'kml' ? $content : $parser->extractKml($tempPath);
            @unlink($tempPath);

            $entryPath = $relativePath.'/'.$entry;
            $collection = $parser->featureCollection($kml, [
                'source_file' => $entryPath,
                'district' => $this->districtFromPath($entryPath),
            ]);

            $files[] = array_merge($this->fileRecord($entryPath, $extension, $zip->statIndex($index)['size'] ?? 0), [
                'placemark_count' => count($collection['features']),
            ]);
            array_push($features, ...$collection['features']);
        }

        $zip->close();

        return ['files' => $files, 'features' => $features];
    }

    private function fileRecord(string $path, string $type, int $size): array
    {
        return [
            'name' => basename($path),
            'path' => $path,
            'district' => $this->districtFromPath($path),
            'type' => $type,
            'size' => $size,
        ];
    }

    private function districtFromPath(string $path): string
    {
        return explode('/', $path)[0] ?? 'Noma\'lum';
    }
}
