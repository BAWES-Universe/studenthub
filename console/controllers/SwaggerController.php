<?php

namespace console\controllers;

use OpenApi\Generator;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Swagger/OpenAPI documentation generator
 * 
 * Scans all API controllers and generates OpenAPI 3.0 JSON specifications
 */
class SwaggerController extends Controller
{
    /**
     * Generate OpenAPI JSON specification for all API modules
     * 
     * @param string $output Output directory (default: api-docs/openapi)
     * @param string $format Output format: json or yaml (default: json)
     * @return int Exit code
     */
    public function actionGenerate($output = null, $format = 'json')
    {
        if ($output === null) {
            $output = Yii::getAlias('@app/../api-docs/openapi');
        }

        // Ensure output directory exists
        if (!is_dir($output)) {
            mkdir($output, 0755, true);
        }

        $this->stdout("Generating OpenAPI specification...\n", Console::FG_GREEN);

        // Define scan paths for all API modules
        $basePath = Yii::getAlias('@app/..');
        $scanPaths = [
            $basePath . '/candidate/modules/v1/controllers',
            $basePath . '/staff/modules/v1/controllers',
            $basePath . '/admin/modules/v1/controllers',
            $basePath . '/company/modules/v1/controllers',
            $basePath . '/manager/modules/v1/controllers',
            $basePath . '/inspector/modules/v1/controllers',
            $basePath . '/status/modules/v1/controllers',
        ];

        // Filter out non-existent paths
        $scanPaths = array_filter($scanPaths, function($path) {
            return is_dir($path);
        });

        if (empty($scanPaths)) {
            $this->stderr("No valid scan paths found!\n", Console::FG_RED);
            return 1;
        }

        try {
            // Generate OpenAPI specification
            $openapi = Generator::scan($scanPaths, [
                'validate' => false, // Set to true for validation (slower)
            ]);

            // Determine file extension
            $extension = $format === 'yaml' ? 'yaml' : 'json';
            $filename = $output . '/openapi.' . $extension;

            // Count documented paths
            $paths = $openapi->paths ?? [];
            $pathCount = count($paths);

            // Write to file
            if ($format === 'yaml') {
                file_put_contents($filename, $openapi->toYaml());
            } else {
                file_put_contents($filename, $openapi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $this->stdout("✓ OpenAPI specification generated successfully!\n", Console::FG_GREEN);
            $this->stdout("  File: {$filename}\n", Console::FG_CYAN);
            $this->stdout("  Scanned " . count($scanPaths) . " controller directories\n", Console::FG_CYAN);
            $this->stdout("  Documented " . $pathCount . " API endpoints\n", Console::FG_CYAN);
            
            if ($pathCount === 0) {
                $this->stdout("\n⚠ Warning: No endpoints found. Add @OA\\* annotations to your controllers.\n", Console::FG_YELLOW);
            } elseif ($pathCount < 10) {
                $this->stdout("\n💡 Tip: Add more @OA\\* annotations to document additional endpoints.\n", Console::FG_YELLOW);
            }

            return 0;
        } catch (\Exception $e) {
            $this->stderr("Error generating OpenAPI specification: " . $e->getMessage() . "\n", Console::FG_RED);
            $this->stderr($e->getTraceAsString() . "\n", Console::FG_RED);
            return 1;
        }
    }

    /**
     * Generate separate OpenAPI specs for each module
     * 
     * @param string $output Output directory (default: api-docs/openapi)
     * @return int Exit code
     */
    public function actionGenerateModules($output = null)
    {
        if ($output === null) {
            $output = Yii::getAlias('@app/../api-docs/openapi');
        }

        if (!is_dir($output)) {
            mkdir($output, 0755, true);
        }

        $this->stdout("Generating OpenAPI specifications for each module...\n", Console::FG_GREEN);

        $modules = [
            'candidate' => 'candidate/modules/v1/controllers',
            'staff' => 'staff/modules/v1/controllers',
            'admin' => 'admin/modules/v1/controllers',
            'company' => 'company/modules/v1/controllers',
            'manager' => 'manager/modules/v1/controllers',
            'inspector' => 'inspector/modules/v1/controllers',
            'status' => 'status/modules/v1/controllers',
        ];

        $basePath = Yii::getAlias('@app/..');
        $successCount = 0;

        foreach ($modules as $moduleName => $relativePath) {
            $scanPath = $basePath . '/' . $relativePath;
            
            if (!is_dir($scanPath)) {
                $this->stdout("  Skipping {$moduleName} (directory not found)\n", Console::FG_YELLOW);
                continue;
            }

            try {
                $openapi = Generator::scan([$scanPath], [
                    'validate' => false,
                ]);

                $filename = $output . '/' . $moduleName . '-openapi.json';
                file_put_contents($filename, $openapi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                $this->stdout("  ✓ Generated {$moduleName}-openapi.json\n", Console::FG_GREEN);
                $successCount++;
            } catch (\Exception $e) {
                $this->stderr("  ✗ Error generating {$moduleName}: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        $this->stdout("\n✓ Generated {$successCount} module specifications\n", Console::FG_GREEN);
        return 0;
    }
}

