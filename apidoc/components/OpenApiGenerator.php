<?php

namespace apidoc\components;

use Yii;
use yii\base\Component;
use yii\rest\UrlRule;

/**
 * Generates OpenAPI 3.0 specification from all Yii2 modules
 */
class OpenApiGenerator extends Component
{
    /**
     * @var array Module configurations to scan
     */
    public $modules = [
        'admin' => [
            'name' => 'Admin',
            'baseUrl' => 'admin.studenthub.local',
            'configPath' => '@app/../admin/config/main.php',
        ],
        'candidate' => [
            'name' => 'Candidate',
            'baseUrl' => 'candidate.studenthub.local',
            'configPath' => '@app/../candidate/config/main.php',
        ],
        'company' => [
            'name' => 'Company',
            'baseUrl' => 'company.studenthub.local',
            'configPath' => '@app/../company/config/main.php',
        ],
        'inspector' => [
            'name' => 'Inspector',
            'baseUrl' => 'inspector.studenthub.local',
            'configPath' => '@app/../inspector/config/main.php',
        ],
        'staff' => [
            'name' => 'Staff',
            'baseUrl' => 'staff.studenthub.local',
            'configPath' => '@app/../staff/config/main.php',
        ],
        'verification' => [
            'name' => 'Verification',
            'baseUrl' => 'verification.studenthub.local',
            'configPath' => '@app/../verification/config/main.php',
        ],
    ];

    /**
     * Generate OpenAPI 3.0 specification
     * @return array
     */
    public function generate()
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'StudentHub API',
                'version' => '1.0.0',
                'description' => 'StudentHub API Documentation - All endpoints from Admin, Candidate, Company, Inspector, Staff, and Verification modules',
            ],
            'servers' => $this->getServers(),
            'tags' => $this->getTags(),
            'x-tagGroups' => $this->getTagGroups(),
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
        ];

        // Generate paths from all modules
               foreach ($this->modules as $moduleKey => $moduleConfig) {
                   $paths = $this->generatePathsForModule($moduleKey, $moduleConfig);
                   $spec['paths'] = array_merge($spec['paths'], $paths);
               }
               
               // Sort all paths globally for consistent ordering
               uksort($spec['paths'], function($a, $b) {
                   $getPrefix = function($path) {
                       $parts = explode('/', trim($path, '/'));
                       return isset($parts[1]) ? $parts[1] : '';
                   };
                   $prefixA = $getPrefix($a);
                   $prefixB = $getPrefix($b);
                   $cmp = strcmp($prefixA, $prefixB);
                   return $cmp !== 0 ? $cmp : strcmp($a, $b);
               });

        return $spec;
    }

    /**
     * Get tags definition for all controllers (without module prefix)
     * @return array
     */
    protected function getTags()
    {
        $tagMap = [];
        
        // Collect all unique controller names across all modules
        foreach ($this->modules as $moduleKey => $moduleConfig) {
            $configPath = Yii::getAlias($moduleConfig['configPath']);
            if (!file_exists($configPath)) {
                continue;
            }
            
            try {
                $config = require $configPath;
                $urlRules = $config['components']['urlManager']['rules'] ?? [];
                
                foreach ($urlRules as $rule) {
                    if (!isset($rule['class']) || $rule['class'] !== 'yii\rest\UrlRule') {
                        continue;
                    }
                    
                    $controller = $rule['controller'] ?? '';
                    $controllerName = $this->getControllerName($controller);
                    
                    // Store controller name only (no module prefix)
                    if (!isset($tagMap[$controllerName])) {
                        $tagMap[$controllerName] = true;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Create tags with just controller names
        $tags = [];
        foreach (array_keys($tagMap) as $controllerName) {
            $tags[] = [
                'name' => $controllerName,
                'description' => "{$controllerName} controller endpoints",
            ];
        }
        
        // Sort alphabetically
        usort($tags, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $tags;
    }

    /**
     * Get tag groups for Scalar subgroups
     * Groups controller tags under module names
     * @return array
     */
    protected function getTagGroups()
    {
        $tagGroups = [];
        
        foreach ($this->modules as $moduleKey => $moduleConfig) {
            $moduleTags = [];
            
            // Collect all controller tags for this module
            $configPath = Yii::getAlias($moduleConfig['configPath']);
            if (!file_exists($configPath)) {
                continue;
            }
            
            try {
                $config = require $configPath;
                $urlRules = $config['components']['urlManager']['rules'] ?? [];
                
                foreach ($urlRules as $rule) {
                    if (!isset($rule['class']) || $rule['class'] !== 'yii\rest\UrlRule') {
                        continue;
                    }
                    
                    $controller = $rule['controller'] ?? '';
                    $controllerName = $this->getControllerName($controller);
                    
                    // Use just controller name (no module prefix)
                    if (!in_array($controllerName, $moduleTags)) {
                        $moduleTags[] = $controllerName;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
            
            if (!empty($moduleTags)) {
                // Sort tags alphabetically within module
                sort($moduleTags);
                $tagGroups[] = [
                    'name' => $moduleConfig['name'],
                    'tags' => $moduleTags,
                ];
            }
        }
        
        return $tagGroups;
    }

    /**
     * Get server URLs based on environment
     * @return array
     */
    protected function getServers()
    {
        $servers = [];
        
        // Detect environment - check hostname to determine environment
        $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $isLocal = strpos($hostname, '.studenthub.local') !== false || YII_ENV === 'dev';
        $isRailwayDev = strpos($hostname, '.dev.studenthub.co') !== false || 
                       (isset($_SERVER['RAILWAY_ENVIRONMENT']) && $_SERVER['RAILWAY_ENVIRONMENT'] === 'development');
        
        foreach ($this->modules as $moduleKey => $moduleConfig) {
            if ($isLocal) {
                $servers[] = [
                    'url' => 'http://' . $moduleConfig['baseUrl'],
                    'description' => "Local {$moduleConfig['name']} API",
                ];
            } elseif ($isRailwayDev) {
                $domain = str_replace('.studenthub.local', '.api.dev.studenthub.co', $moduleConfig['baseUrl']);
                if ($moduleKey === 'verification') {
                    $domain = 'v.dev.studenthub.co';
                }
                $servers[] = [
                    'url' => 'https://' . $domain,
                    'description' => "Dev {$moduleConfig['name']} API",
                ];
            } else {
                // Production
                $domain = str_replace('.studenthub.local', '.api.studenthub.co', $moduleConfig['baseUrl']);
                if ($moduleKey === 'verification') {
                    $domain = 'v.studenthub.co';
                }
                $servers[] = [
                    'url' => 'https://' . $domain,
                    'description' => "Production {$moduleConfig['name']} API",
                ];
            }
        }

        return $servers;
    }

    /**
     * Generate OpenAPI paths for a specific module
     * @param string $moduleKey
     * @param array $moduleConfig
     * @return array
     */
    protected function generatePathsForModule($moduleKey, $moduleConfig)
    {
        $paths = [];
        $configPath = Yii::getAlias($moduleConfig['configPath']);
        
        if (!file_exists($configPath)) {
            return $paths;
        }

        try {
            $config = require $configPath;
            $urlRules = $config['components']['urlManager']['rules'] ?? [];
        } catch (\Exception $e) {
            // Config file might have dependencies that aren't available
            // Return empty paths for this module
            return $paths;
        }

        foreach ($urlRules as $rule) {
            if (!isset($rule['class']) || $rule['class'] !== 'yii\rest\UrlRule') {
                continue;
            }

            $controller = $rule['controller'] ?? '';
            $patterns = $rule['patterns'] ?? [];
            $pluralize = $rule['pluralize'] ?? true;

            // Determine base path
            $basePath = '/' . $controller;
            if ($pluralize && !isset($rule['pluralize']) || $rule['pluralize'] !== false) {
                // Simple pluralization (add 's')
                $basePath = rtrim($basePath, '/') . 's';
            }

            // Process each pattern
            foreach ($patterns as $pattern => $action) {
                if (strpos($pattern, 'OPTIONS') === 0) {
                    continue; // Skip OPTIONS
                }

                $path = $this->convertPatternToPath($basePath, $pattern, $action);
                $method = $this->extractMethod($pattern);
                
                if (!$path || !$method) {
                    continue;
                }

                $pathKey = $path;
                if (!isset($paths[$pathKey])) {
                    $paths[$pathKey] = [];
                }

                $controllerName = $this->getControllerName($controller);
                // Use only controller name as tag - will be grouped by module via x-tagGroups
                $paths[$pathKey][strtolower($method)] = [
                    'tags' => [$controllerName],
                    'summary' => $this->getActionSummary($action),
                    'description' => $this->getActionDescription($controller, $action, $moduleConfig['name']),
                    'security' => [
                        ['bearerAuth' => []]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object'
                                    ]
                                ]
                            ]
                        ],
                        '401' => [
                            'description' => 'Unauthorized'
                        ],
                        '403' => [
                            'description' => 'Forbidden'
                        ],
                        '404' => [
                            'description' => 'Not Found'
                        ],
                    ],
                ];

                // Add parameters for path variables
                $pathParams = $this->extractPathParameters($path);
                if (!empty($pathParams)) {
                    $paths[$pathKey][strtolower($method)]['parameters'] = [];
                    foreach ($pathParams as $param) {
                        $paths[$pathKey][strtolower($method)]['parameters'][] = [
                            'name' => $param,
                            'in' => 'path',
                            'required' => true,
                            'schema' => [
                                'type' => 'string'
                            ]
                        ];
                    }
                }
            }
        }

        // Sort paths by controller prefix for logical grouping
        // This ensures endpoints from the same controller appear together
        uksort($paths, function($a, $b) {
            // Extract controller prefix (e.g., /v1/account -> account, /v1/auth/login -> auth)
            $getPrefix = function($path) {
                $parts = explode('/', trim($path, '/'));
                return isset($parts[1]) ? $parts[1] : '';
            };
            $prefixA = $getPrefix($a);
            $prefixB = $getPrefix($b);
            
            // Compare by controller prefix first
            $cmp = strcmp($prefixA, $prefixB);
            if ($cmp !== 0) {
                return $cmp;
            }
            // If same prefix, sort by full path
            return strcmp($a, $b);
        });

        return $paths;
    }

    /**
     * Convert Yii2 pattern to OpenAPI path
     * @param string $basePath
     * @param string $pattern
     * @param string $action
     * @return string|null
     */
    protected function convertPatternToPath($basePath, $pattern, $action)
    {
        // Remove HTTP method from pattern (e.g., "GET config" -> "config", "GET" -> "")
        // Make space optional to handle both "GET" and "GET config" patterns
        $path = preg_replace('/^(GET|POST|PUT|PATCH|DELETE|HEAD)\s*/', '', $pattern);
        $path = trim($path);
        
        // If pattern is just the method (e.g., "GET"), path is empty, use base path
        if (empty($path)) {
            return $basePath;
        }
        
        // Handle special Yii2 patterns - convert to OpenAPI format
        $path = str_replace('<id>', '{id}', $path);
        $path = str_replace('<request_uuid>', '{request_uuid}', $path);
        $path = str_replace('<candidate_uid>', '{candidate_uid}', $path);
        $path = str_replace('<ticket_uuid>', '{ticket_uuid}', $path);
        $path = str_replace('<place_id>', '{place_id}', $path);
        $path = str_replace('<wid>', '{wid}', $path);
        $path = str_replace('<date>', '{date}', $path);
        $path = str_replace('<candidateId>', '{candidateId}', $path);
        $path = str_replace('<token>', '{token}', $path);
        
        // If path equals action and it's a standard REST action, use base path
        // Otherwise, the path is a custom endpoint (e.g., "config", "click/<id>")
        $standardActions = ['list', 'view', 'create', 'update', 'delete', 'test'];
        if (in_array($action, $standardActions) && $path === $action) {
            return $basePath;
        }

        // Handle absolute paths (starting with /) - shouldn't happen with REST rules, but handle it
        if (strpos($path, '/') === 0) {
            return $path;
        }

        // Combine with base path (e.g., /v1/cron-log + config -> /v1/cron-log/config)
        return rtrim($basePath, '/') . '/' . $path;
    }

    /**
     * Extract HTTP method from pattern
     * @param string $pattern
     * @return string|null
     */
    protected function extractMethod($pattern)
    {
        if (preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD)/', $pattern, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract path parameters from OpenAPI path
     * @param string $path
     * @return array
     */
    protected function extractPathParameters($path)
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Get controller name for tag
     * @param string $controller
     * @return string
     */
    protected function getControllerName($controller)
    {
        $parts = explode('/', $controller);
        $name = end($parts);
        return ucfirst(str_replace('Controller', '', $name));
    }

    /**
     * Get action summary
     * @param string $action
     * @return string
     */
    protected function getActionSummary($action)
    {
        $actionMap = [
            'list' => 'List items',
            'view' => 'View item',
            'create' => 'Create item',
            'update' => 'Update item',
            'delete' => 'Delete item',
            'test' => 'Test endpoint',
            'config' => 'Get configuration',
            'detail' => 'Get details',
            'click' => 'Click action',
        ];

        return $actionMap[$action] ?? ucfirst(str_replace('-', ' ', $action));
    }

    /**
     * Get action description
     * @param string $controller
     * @param string $action
     * @param string $moduleName
     * @return string
     */
    protected function getActionDescription($controller, $action, $moduleName = '')
    {
        $desc = "{$action} action for {$controller}";
        if ($moduleName) {
            $desc .= " in {$moduleName} module";
        }
        return $desc;
    }
}

