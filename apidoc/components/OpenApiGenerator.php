<?php

namespace apidoc\components;

use Yii;
use yii\base\Component;
use yii\rest\UrlRule;

/**
 * Generates OpenAPI 3.0 specification from all Yii2 modules
 * Professional-grade API documentation generator
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
                'description' => $this->getApiDescription(),
                'contact' => [
                    'name' => 'StudentHub API Support',
                ],
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
                        'description' => 'JWT Bearer token authentication. Obtain a token by authenticating with Basic Auth or login endpoints.',
                    ],
                    'basicAuth' => [
                        'type' => 'http',
                        'scheme' => 'basic',
                        'description' => 'HTTP Basic Authentication using email and password. Used for initial authentication to obtain a Bearer token.',
                    ],
                ],
                'schemas' => $this->getCommonSchemas(),
            ],
        ];

        // Generate paths from all modules - keep them organized by module
        // Don't mix paths globally - each module's paths stay together
        foreach ($this->modules as $moduleKey => $moduleConfig) {
            $paths = $this->generatePathsForModule($moduleKey, $moduleConfig);
            $spec['paths'] = array_merge($spec['paths'], $paths);
        }
        
        // Paths are already sorted within each module in generatePathsForModule()
        // No global sorting needed - keeps modules separated

        return $spec;
    }

    /**
     * Get API description
     * @return string
     */
    protected function getApiDescription()
    {
        return <<<DESC
# StudentHub API Documentation

Welcome to the StudentHub API. This API provides comprehensive access to all StudentHub platform features across multiple modules.

## Authentication

Most endpoints require authentication using a Bearer token. To obtain a token:

1. Use HTTP Basic Authentication with your email and password on login endpoints
2. Receive a JWT Bearer token in the response
3. Include the token in the `Authorization` header for subsequent requests: `Authorization: Bearer <token>`

## Modules

- **Admin**: Administrative operations and system management
- **Candidate**: Candidate profile and application management
- **Company**: Company account and job posting management
- **Inspector**: Inspection and verification workflows
- **Staff**: Staff operations and candidate management
- **Verification**: Email and document verification

## Rate Limiting

API requests are subject to rate limiting. Check response headers for rate limit information.

## Support

For API support, please contact the StudentHub development team.
DESC;
    }

    /**
     * Get common schemas for reuse
     * @return array
     */
    protected function getCommonSchemas()
    {
        return [
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'operation' => [
                        'type' => 'string',
                        'example' => 'error',
                    ],
                    'message' => [
                        'type' => 'string',
                        'description' => 'Human-readable error message',
                    ],
                    'code' => [
                        'type' => 'integer',
                        'description' => 'Error code',
                    ],
                    'errorType' => [
                        'type' => 'string',
                        'description' => 'Type of error',
                    ],
                ],
            ],
            'LoginRequest' => [
                'type' => 'object',
                'required' => ['email', 'password'],
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'User email address',
                        'example' => 'user@example.com',
                    ],
                    'password' => [
                        'type' => 'string',
                        'format' => 'password',
                        'description' => 'User password',
                    ],
                ],
            ],
            'LoginResponse' => [
                'type' => 'object',
                'properties' => [
                    'operation' => [
                        'type' => 'string',
                        'example' => 'success',
                    ],
                    'token' => [
                        'type' => 'string',
                        'description' => 'JWT Bearer token for authentication',
                    ],
                    'user' => [
                        'type' => 'object',
                        'description' => 'User information',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tags definition - simplified to just controller names
     * Tags are scoped by module via x-tagGroups, so no conflicts
     * @return array
     */
    protected function getTags()
    {
        $tagMap = [];
        
        // Collect all unique controller names (scoped by module in x-tagGroups)
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
                    $controllerName = $this->getControllerName($controller, $moduleConfig['name']);
                    // Use just controller name - scoped by module in x-tagGroups
                    $tag = $controllerName;
                    
                    if (!isset($tagMap[$tag])) {
                        $tagMap[$tag] = [
                            'name' => $tag,
                            'description' => "{$controllerName} endpoints",
                        ];
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Sort tags alphabetically
        $tags = array_values($tagMap);
        usort($tags, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $tags;
    }

    /**
     * Get tag groups for Scalar subgroups
     * Groups controller tags under module names with logical ordering
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
                    $controllerName = $this->getControllerName($controller, $moduleConfig['name']);
                    // Use just controller name - scoped by module
                    $tag = $controllerName;
                    
                    if (!in_array($tag, $moduleTags)) {
                        $moduleTags[] = $tag;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
            
            if (!empty($moduleTags)) {
                // Sort tags logically: Auth first, then Account, then by logical groups
                $sortedTags = $this->sortTagsLogically($moduleTags);
                $tagGroups[] = [
                    'name' => $moduleConfig['name'],
                    'tags' => $sortedTags,
                ];
            }
        }
        
        return $tagGroups;
    }

    /**
     * Sort tags logically within a module
     * Priority: Auth → Account → Registration → Core features → Utilities
     * @param array $tags
     * @return array
     */
    protected function sortTagsLogically($tags)
    {
        // Define priority order - exact matches first
        $exactPriority = [
            'auth' => 1,
            'account' => 2,
            'signup' => 3,
            'register' => 3,
            'registration' => 3,
        ];
        
        // Define prefix-based priorities for controllers that start with these
        $prefixPriority = [
            'account' => 2,
            'candidate' => 10,
            'request' => 20,
            'transfer' => 30,
            'company' => 40,
            'staff' => 50,
            'chat' => 60,
            'note' => 70,
            'job' => 80,
            'store' => 90,
            'bank' => 100,
            'brand' => 100,
            'campaign' => 100,
            'contract' => 100,
            'discount' => 100,
            'invitation' => 100,
            'tag' => 100,
            'university' => 100,
            'country' => 100,
            'currency' => 100,
            'degree' => 100,
            'major' => 100,
            'mall' => 100,
            'webhook' => 100,
            'xero' => 100,
            'yeaster' => 100,
            'algolia' => 200,
            'aws' => 200,
            'ping' => 200,
            'config' => 200,
            'cron-log' => 200,
            'statistic' => 200,
            'setting' => 200,
        ];
        
        // Categorize tags
        $categorized = [];
        
        foreach ($tags as $tag) {
            $tagLower = strtolower($tag);
            $priority = 999; // Default low priority
            
            // Check exact match first
            if (isset($exactPriority[$tagLower])) {
                $priority = $exactPriority[$tagLower];
            } else {
                // Check prefix matches
                foreach ($prefixPriority as $prefix => $prio) {
                    if ($tagLower === $prefix || strpos($tagLower, $prefix . '-') === 0 || strpos($tagLower, $prefix) === 0) {
                        $priority = $prio;
                        break;
                    }
                }
            }
            
            $categorized[] = ['tag' => $tag, 'priority' => $priority];
        }
        
        // Sort by priority, then alphabetically
        usort($categorized, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] - $b['priority'];
            }
            return strcmp($a['tag'], $b['tag']);
        });
        
        // Return just the tags
        return array_map(function($item) {
            return $item['tag'];
        }, $categorized);
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
                    'description' => "Development {$moduleConfig['name']} API",
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

                $controllerName = $this->getControllerName($controller, $moduleConfig['name']);
                // Use just controller name - scoped by module in x-tagGroups
                $tag = $controllerName;
                
                // Build path data
                $pathData = $this->buildPathData($tag, $controller, $action, $moduleConfig['name'], $method, $path);

                $paths[$pathKey][strtolower($method)] = $pathData;

                // Add parameters for path variables
                $pathParams = $this->extractPathParameters($path);
                if (!empty($pathParams)) {
                    $paths[$pathKey][strtolower($method)]['parameters'] = [];
                    foreach ($pathParams as $param) {
                        $paths[$pathKey][strtolower($method)]['parameters'][] = [
                            'name' => $param,
                            'in' => 'path',
                            'required' => true,
                            'description' => $this->getParameterDescription($param),
                            'schema' => [
                                'type' => 'string'
                            ]
                        ];
                    }
                }
            }
        }

        // Sort paths logically within this module
        // Priority: Auth endpoints first, then by controller, then by HTTP method
        uksort($paths, function($a, $b) use ($paths) {
            // Get controller prefix
            $getController = function($path) {
                $parts = explode('/', trim($path, '/'));
                return isset($parts[1]) ? $parts[1] : '';
            };
            
            $controllerA = $getController($a);
            $controllerB = $getController($b);
            
            // Auth endpoints first
            $isAuthA = $controllerA === 'auth';
            $isAuthB = $controllerB === 'auth';
            if ($isAuthA && !$isAuthB) return -1;
            if (!$isAuthA && $isAuthB) return 1;
            
            // Account endpoints second
            $isAccountA = $controllerA === 'account';
            $isAccountB = $controllerB === 'account';
            if ($isAccountA && !$isAccountB && !$isAuthB) return -1;
            if (!$isAccountA && $isAccountB && !$isAuthA) return 1;
            
            // Then by controller name
            $cmp = strcmp($controllerA, $controllerB);
            if ($cmp !== 0) {
                return $cmp;
            }
            
            // Within same controller, sort by HTTP method priority
            $methodPriority = ['get' => 1, 'post' => 2, 'patch' => 3, 'put' => 4, 'delete' => 5];
            $methodsA = array_keys($paths[$a] ?? []);
            $methodsB = array_keys($paths[$b] ?? []);
            $methodA = $methodsA[0] ?? '';
            $methodB = $methodsB[0] ?? '';
            $priorityA = $methodPriority[$methodA] ?? 99;
            $priorityB = $methodPriority[$methodB] ?? 99;
            
            if ($priorityA !== $priorityB) {
                return $priorityA - $priorityB;
            }
            
            // Finally by path
            return strcmp($a, $b);
        });

        return $paths;
    }

    /**
     * Build path data with professional structure
     * @param string $tag
     * @param string $controller
     * @param string $action
     * @param string $moduleName
     * @param string $method
     * @param string $path
     * @return array
     */
    protected function buildPathData($tag, $controller, $action, $moduleName, $method, $path)
    {
        $isAuthEndpoint = strpos(strtolower($controller), 'auth') !== false;
        $isLoginAction = in_array(strtolower($action), ['login', 'login-two-step', 'login-auth0', 'login-by-google', 'login-by-key']);
        $isPublicAction = in_array(strtolower($action), ['options', 'ping', 'test']);

        $pathData = [
            'tags' => [$tag],
            'summary' => $this->getActionSummary($action, $controller),
            'description' => $this->getActionDescription($controller, $action, $moduleName),
            'responses' => $this->getResponseSchemas($isLoginAction),
        ];

        // Handle authentication
        if ($isAuthEndpoint && $isLoginAction) {
            // Login endpoints use Basic Auth
            $pathData['security'] = [
                ['basicAuth' => []]
            ];
            
            // Add request body for POST/PATCH login endpoints
            if (in_array(strtolower($method), ['post', 'patch'])) {
                $pathData['requestBody'] = $this->getLoginRequestBody($action);
            }
        } elseif (!$isPublicAction) {
            // Other endpoints use Bearer Auth
            $pathData['security'] = [
                ['bearerAuth' => []]
            ];
        }

        // Add request body for POST/PATCH/PUT endpoints (except login)
        if (!$isLoginAction && in_array(strtolower($method), ['post', 'patch', 'put'])) {
            $pathData['requestBody'] = $this->getRequestBody($action, $controller);
        }

        return $pathData;
    }

    /**
     * Get login request body schema
     * @param string $action
     * @return array
     */
    protected function getLoginRequestBody($action)
    {
        $body = [
            'required' => true,
            'description' => 'Login credentials',
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => '#/components/schemas/LoginRequest'
                    ]
                ]
            ]
        ];

        // Special handling for different login types
        if ($action === 'login-by-key') {
            $body['content']['application/json']['schema'] = [
                'type' => 'object',
                'required' => ['auth_key'],
                'properties' => [
                    'auth_key' => [
                        'type' => 'string',
                        'description' => 'Authentication key',
                    ],
                ],
            ];
        } elseif ($action === 'login-auth0') {
            $body['content']['application/json']['schema'] = [
                'type' => 'object',
                'required' => ['accessToken'],
                'properties' => [
                    'accessToken' => [
                        'type' => 'string',
                        'description' => 'Auth0 access token',
                    ],
                ],
            ];
        } elseif ($action === 'login-two-step') {
            $body['content']['application/json']['schema'] = [
                'type' => 'object',
                'required' => ['token', 'otp'],
                'properties' => [
                    'token' => [
                        'type' => 'string',
                        'description' => 'Temporary authentication token',
                    ],
                    'otp' => [
                        'type' => 'string',
                        'description' => 'One-time password',
                    ],
                ],
            ];
        }

        return $body;
    }

    /**
     * Get generic request body schema
     * @param string $action
     * @param string $controller
     * @return array
     */
    protected function getRequestBody($action, $controller)
    {
        return [
            'required' => true,
            'description' => 'Request payload',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'description' => 'Request body parameters',
                    ]
                ]
            ]
        ];
    }

    /**
     * Get response schemas
     * @param bool $isLogin
     * @return array
     */
    protected function getResponseSchemas($isLogin = false)
    {
        $responses = [
            '200' => [
                'description' => 'Successful response',
                'content' => [
                    'application/json' => [
                        'schema' => $isLogin ? [
                            '$ref' => '#/components/schemas/LoginResponse'
                        ] : [
                            'type' => 'object',
                            'description' => 'Response data',
                        ]
                    ]
                ]
            ],
            '400' => [
                'description' => 'Bad Request - Invalid parameters',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '401' => [
                'description' => 'Unauthorized - Invalid or missing authentication',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '403' => [
                'description' => 'Forbidden - Insufficient permissions',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '404' => [
                'description' => 'Not Found - Resource does not exist',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
            '500' => [
                'description' => 'Internal Server Error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/Error'
                        ]
                    ]
                ]
            ],
        ];

        return $responses;
    }

    /**
     * Get parameter description
     * @param string $param
     * @return string
     */
    protected function getParameterDescription($param)
    {
        $descriptions = [
            'id' => 'Resource identifier',
            'request_uuid' => 'Request unique identifier',
            'candidate_uid' => 'Candidate unique identifier',
            'ticket_uuid' => 'Ticket unique identifier',
            'place_id' => 'Place identifier',
            'wid' => 'Work identifier',
            'date' => 'Date parameter',
            'candidateId' => 'Candidate identifier',
            'token' => 'Token parameter',
        ];

        return $descriptions[$param] ?? ucfirst(str_replace('_', ' ', $param));
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
        $standardActions = ['list', 'view', 'create', 'update', 'delete', 'test'];
        if (in_array($action, $standardActions) && $path === $action) {
            return $basePath;
        }

        // Handle absolute paths (starting with /)
        if (strpos($path, '/') === 0) {
            return $path;
        }

        // Combine with base path
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
     * @param string $moduleName Optional module name to remove from controller name
     * @return string
     */
    protected function getControllerName($controller, $moduleName = '')
    {
        $parts = explode('/', $controller);
        $name = end($parts);
        $name = ucfirst(str_replace('Controller', '', $name));
        
        // Remove module name prefix from controller name to avoid redundancy
        // e.g., "Candidate-education" in Candidate module -> "Education"
        if ($moduleName) {
            $moduleLower = strtolower($moduleName);
            $nameLower = strtolower($name);
            
            // Check if controller name starts with module name
            if (strpos($nameLower, $moduleLower) === 0) {
                // Remove module name and following dash/hyphen
                $name = substr($name, strlen($moduleName));
                $name = ltrim($name, '-');
                // Capitalize first letter
                if (!empty($name)) {
                    $name = ucfirst($name);
                } else {
                    // If name becomes empty, use module name
                    $name = $moduleName;
                }
            }
        }
        
        return $name;
    }

    /**
     * Get action summary
     * @param string $action
     * @param string $controller
     * @return string
     */
    protected function getActionSummary($action, $controller = '')
    {
        $actionMap = [
            'list' => 'List all items',
            'view' => 'Retrieve a specific item',
            'create' => 'Create a new item',
            'update' => 'Update an existing item',
            'delete' => 'Delete an item',
            'test' => 'Test endpoint connectivity',
            'config' => 'Get configuration',
            'detail' => 'Get detailed information',
            'click' => 'Record click event',
            'login' => 'Authenticate and obtain access token',
            'login-two-step' => 'Complete two-step authentication',
            'login-auth0' => 'Authenticate using Auth0',
            'login-by-google' => 'Authenticate using Google',
            'login-by-key' => 'Authenticate using authentication key',
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
        $controllerName = $this->getControllerName($controller);
        $actionName = ucfirst(str_replace('-', ' ', $action));
        
        $desc = "{$actionName} operation for {$controllerName}";
        if ($moduleName) {
            $desc .= " in the {$moduleName} module";
        }
        
        return $desc . '.';
    }
}
