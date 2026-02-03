<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TransferController extends Controller
{
    public function index()
    {
        return view('transfer');
    }

    public function herdProjects()
    {
        // Common Herd paths
        $herdPaths = [
            $_SERVER['HOME'] . '/Herd',
            $_SERVER['HOME'] . '/Documents/GitHub',
        ];

        $projects = [];

        foreach ($herdPaths as $basePath) {
            if (!File::isDirectory($basePath)) {
                continue;
            }

            $directories = File::directories($basePath);

            foreach ($directories as $dir) {
                // Check if it looks like a Statamic/Laravel project
                $isStatamic = File::exists($dir . '/config/statamic') ||
                              (File::exists($dir . '/composer.json') &&
                               str_contains(File::get($dir . '/composer.json'), 'statamic'));

                $isLaravel = File::exists($dir . '/artisan');

                if ($isStatamic || $isLaravel) {
                    $projects[] = [
                        'name' => basename($dir),
                        'path' => $dir,
                        'isStatamic' => $isStatamic,
                    ];
                }
            }
        }

        // Sort alphabetically
        usort($projects, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return response()->json(['projects' => $projects]);
    }

    public function browse(Request $request)
    {
        $path = $request->input('path');

        if (!$path || !File::isDirectory($path)) {
            return response()->json(['error' => 'Invalid directory'], 400);
        }

        // Check if it's a Statamic project
        $isStatamic = File::exists($path . '/config/statamic') ||
                      (File::exists($path . '/composer.json') &&
                       str_contains(File::get($path . '/composer.json'), 'statamic'));

        $files = $this->getProjectFiles($path);

        return response()->json([
            'isStatamic' => $isStatamic,
            'files' => $files,
            'path' => $path
        ]);
    }

    private function getProjectFiles(string $basePath): array
    {
        $relevantDirs = [
            'resources/views',
            'resources/js/components',
            'resources/fieldsets',
            'resources/blueprints',
        ];

        $files = [];

        foreach ($relevantDirs as $dir) {
            $fullPath = $basePath . '/' . $dir;
            if (File::isDirectory($fullPath)) {
                $files = array_merge($files, $this->scanDirectory($fullPath, $basePath));
            }
        }

        return $files;
    }

    private function scanDirectory(string $dir, string $basePath): array
    {
        $files = [];
        $items = File::allFiles($dir);

        foreach ($items as $item) {
            $relativePath = str_replace($basePath . '/', '', $item->getPathname());
            $extension = $item->getExtension();

            // Filter for relevant file types
            if (in_array($extension, ['html', 'vue', 'yaml', 'yml', 'js', 'php']) ||
                str_contains($item->getFilename(), '.antlers.')) {
                $files[] = [
                    'name' => $item->getFilename(),
                    'path' => $relativePath,
                    'fullPath' => $item->getPathname(),
                    'type' => $this->getFileType($item->getFilename()),
                ];
            }
        }

        return $files;
    }

    private function getFileType(string $filename): string
    {
        if (str_contains($filename, '.antlers.html')) return 'antlers';
        if (str_ends_with($filename, '.vue')) return 'vue';
        if (str_ends_with($filename, '.yaml') || str_ends_with($filename, '.yml')) return 'yaml';
        if (str_ends_with($filename, '.js')) return 'js';
        return 'other';
    }

    public function preview(Request $request)
    {
        $path = $request->input('path');

        if (!$path || !File::exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->json([
            'content' => File::get($path),
            'path' => $path
        ]);
    }

    public function execute(Request $request)
    {
        $transfers = $request->input('transfers', []);
        $results = [];

        foreach ($transfers as $transfer) {
            $sourcePath = $transfer['sourcePath'];
            $destPath = $transfer['destPath'];
            $newName = $transfer['newName'] ?? basename($sourcePath);

            $fullDestPath = rtrim($destPath, '/') . '/' . $newName;

            try {
                // Ensure destination directory exists
                $destDir = dirname($fullDestPath);
                if (!File::isDirectory($destDir)) {
                    File::makeDirectory($destDir, 0755, true);
                }

                File::copy($sourcePath, $fullDestPath);

                $results[] = [
                    'source' => $sourcePath,
                    'destination' => $fullDestPath,
                    'success' => true
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'source' => $sourcePath,
                    'destination' => $fullDestPath,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}
