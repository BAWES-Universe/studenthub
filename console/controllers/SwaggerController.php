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
 * with environment-aware server URLs
 */
class SwaggerController extends Controller
{
    /**
     * Module to URL pattern mapping
     * Maps module names to their local and API subdomain patterns
     */
    private function getModuleUrlPatterns()
    {
        return [
            'candidate' => ['local' => 'candidate', 'api' => 'student.api'],
            'staff' => ['local' => 'staff', 'api' => 'staff.api'],
            'admin' => ['local' => 'admin', 'api' => 'admin.api'],
            'company' => ['local' => 'company', 'api' => 'employer.api'],
            'manager' => ['local' => 'manager', 'api' => 'manager.api'],
            'inspector' => ['local' => 'inspector', 'api' => 'inspector.api'],
            'status' => ['local' => 'status', 'api' => 'status.api'],
        ];
    }

    /**
     * Detect current deployment environment
     * 
     * @return string 'local', 'dev', or 'prod'
     */
    private function detectEnvironment()
    {
        // Check YII_ENV constant (works in both web and console)
        if (defined('YII_ENV')) {
            $env = YII_ENV;
            if ($env === 'Development') return 'dev';
            if ($env === 'Production') return 'prod';
        }
        
        // Check if running locally (Traefik) - web context
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'studenthub.local') !== false) {
            return 'local';
        }
        
        // Check hostname pattern - web context
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, '.dev.') !== false) return 'dev';
            if (strpos($host, '.studenthub.co') !== false && strpos($host, '.dev.') === false) {
                return 'prod';
            }
        }
        
        // Check environment directory structure (console context)
        $basePath = Yii::getAlias('@app/..');
        $envPath = $basePath . '/environments';
        if (is_dir($envPath)) {
            // Check which environment directory exists or is active
            if (is_dir($envPath . '/dev')) {
                // Check if we're in dev environment by looking at config
                $devConfig = $envPath . '/dev/common/config/main-local.php';
                if (file_exists($devConfig)) {
                    // Could be dev, but also check for local indicators
                    // For now, if dev config exists and we can't determine otherwise, assume dev
                }
            }
        }
        
        // Default to local for development
        return 'local';
    }

    /**
     * Generate server URL for a module in the current environment
     * 
     * @param string $module Module name (candidate, staff, admin, etc.)
     * @param string $environment Environment (local, dev, prod)
     * @return string|null Server URL or null if module not found
     */
    private function generateServerUrl($module, $environment)
    {
        $patterns = $this->getModuleUrlPatterns();
        $modulePattern = $patterns[$module] ?? null;
        
        if (!$modulePattern) {
            return null;
        }
        
        switch ($environment) {
            case 'local':
                return "http://{$modulePattern['local']}.studenthub.local/v1";
            case 'dev':
                return "https://{$modulePattern['api']}.dev.studenthub.co/v1";
            case 'prod':
                return "https://{$modulePattern['api']}.studenthub.co/v1";
            default:
                return "http://{$modulePattern['local']}.studenthub.local/v1";
        }
    }

    /**
     * Extract module name from scan path
     * 
     * @param string $path Full path to controller directory
     * @return string|null Module name or null if not found
     */
    private function extractModuleFromPath($path)
    {
        // Extract module from path like: /path/to/candidate/modules/v1/controllers
        if (preg_match('#/(\w+)/modules/v1/controllers#', $path, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Modify OpenAPI JSON to inject server URL
     * 
     * @param string $json OpenAPI JSON string
     * @param string $serverUrl Server URL to inject
     * @param string $description Server description
     * @return string Modified JSON string
     */
    private function injectServerUrlIntoJson($json, $serverUrl, $description = null)
    {
        if (!$serverUrl) {
            return $json;
        }
        
        $spec = json_decode($json, true);
        if (!$spec) {
            return $json;
        }
        
        // Set servers array
        $spec['servers'] = [[
            'url' => $serverUrl,
            'description' => $description ?: 'Current environment'
        ]];
        
        return json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
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
            // Detect current environment
            $environment = $this->detectEnvironment();
            $this->stdout("  Detected environment: {$environment}\n", Console::FG_CYAN);
            
            // Generate OpenAPI specification
            $openapi = Generator::scan($scanPaths, [
                'validate' => false, // Set to true for validation (slower)
            ]);

            // Detect module from first scan path (for unified spec, use first module found)
            $firstModule = null;
            foreach ($scanPaths as $path) {
                $module = $this->extractModuleFromPath($path);
                if ($module) {
                    $firstModule = $module;
                    break;
                }
            }
            
            // Determine file extension
            $extension = $format === 'yaml' ? 'yaml' : 'json';
            $filename = $output . '/openapi.' . $extension;

            // Count documented paths
            $paths = $openapi->paths ?? [];
            $pathCount = count($paths);

            // Generate server URL for detected module and environment
            $outputContent = null;
            if ($firstModule) {
                $serverUrl = $this->generateServerUrl($firstModule, $environment);
                if ($serverUrl) {
                    $this->stdout("  Server URL: {$serverUrl}\n", Console::FG_CYAN);
                    
                    // Modify JSON/YAML to inject server URL
                    if ($format === 'yaml') {
                        $yaml = $openapi->toYaml();
                        // For YAML, we'd need a YAML parser, but JSON is simpler
                        // Convert to JSON, modify, convert back if needed
                        $json = $openapi->toJson();
                        $modifiedJson = $this->injectServerUrlIntoJson($json, $serverUrl, ucfirst($environment) . ' environment');
                        // For now, write as JSON even if yaml requested (or implement YAML modification)
                        $outputContent = $modifiedJson;
                    } else {
                        $json = $openapi->toJson();
                        $outputContent = $this->injectServerUrlIntoJson($json, $serverUrl, ucfirst($environment) . ' environment');
                    }
                }
            }
            
            // Write to file
            if ($outputContent) {
                file_put_contents($filename, $outputContent);
            } else {
                // Fallback to original if no server URL injection
                if ($format === 'yaml') {
                    file_put_contents($filename, $openapi->toYaml());
                } else {
                    file_put_contents($filename, $openapi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
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

        // Detect current environment
        $environment = $this->detectEnvironment();
        $this->stdout("  Detected environment: {$environment}\n", Console::FG_CYAN);

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

                // Generate server URL for this module in current environment
                $serverUrl = $this->generateServerUrl($moduleName, $environment);
                $json = $openapi->toJson();
                
                if ($serverUrl) {
                    $json = $this->injectServerUrlIntoJson($json, $serverUrl, ucfirst($environment) . ' environment');
                    $this->stdout("  Server URL for {$moduleName}: {$serverUrl}\n", Console::FG_CYAN);
                }

                $filename = $output . '/' . $moduleName . '-openapi.json';
                file_put_contents($filename, $json);

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

