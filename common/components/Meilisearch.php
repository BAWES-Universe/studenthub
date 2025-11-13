<?php

namespace common\components;

use MeiliSearch\Client as MeiliSearchClient;
use Yii;

/**
 * Meilisearch component for search functionality
 * Mirrors Algolia component interface for easy migration
 * 
 * Usage:
 * $result = Yii::$app->meilisearch->search('dev_candidate_public', 'search term');
 */
class Meilisearch extends \yii\base\Component {
    
    public $host;
    
    public $masterKey;
    
    private $_client;
        
    /**
     * Generate temporary search key with restrictions
     * 
     * @param array $params Restrictions for the key:
     *   - 'indexes' => array of index names to restrict access to
     *   - 'expiresAt' => Unix timestamp when key expires
     *   - 'description' => Optional description
     * @return string Temporary search key
     */
    public function generateSearchKey($params = []) 
    {
        $client = $this->getClient();
        
        $keyParams = [];
        
        // Set expiration if provided
        if (isset($params['expiresAt'])) {
            $keyParams['expiresAt'] = date('c', $params['expiresAt']);
        }
        
        // Set index restrictions if provided
        if (isset($params['indexes']) && is_array($params['indexes'])) {
            $keyParams['indexes'] = $params['indexes'];
        }
        
        // Set description if provided
        if (isset($params['description'])) {
            $keyParams['description'] = $params['description'];
        }
        
        // Set actions - search only for security
        $keyParams['actions'] = ['search'];
        
        try {
            $keyObject = $client->createKey($keyParams);
            // Meilisearch PHP client returns a Key object, not an array
            // Use getKey() method to get the actual key string
            return $keyObject->getKey();
        } catch (\Exception $e) {
            Yii::error('Failed to generate Meilisearch key: ' . $e->getMessage());
            throw $e;
        }
    }
      
    /**
     * Add object to index
     * @param string $index
     * @param array $data Must include 'id' or 'objectID' field
     * @return array
     */
    public function add($index, $data)
    {
        // Meilisearch uses 'id' instead of 'objectID', but we'll support both
        if (isset($data['objectID']) && !isset($data['id'])) {
            $data['id'] = $data['objectID'];
        }
        
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->addDocuments([$data]);
    }
    
    /**
     * Partially update object in index
     * $data = [
     *    'id' => 'myID2',  // or 'objectID'
     *    'firstname' => 'Warren'
     * ]
     * @param string $index
     * @param array $data
     * @return array
     */
    public function partialUpdate($index, $data) 
    {
        // Meilisearch uses 'id' instead of 'objectID'
        if (isset($data['objectID']) && !isset($data['id'])) {
            $data['id'] = $data['objectID'];
            unset($data['objectID']);
        }
        
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->updateDocuments([$data]);
    }
    
    /**
     * Partially update multiple objects in index
     * $data = [
     *    [
     *        'id' => 'myID1',  // or 'objectID'
     *        'firstname' => 'Jimmie'
     *    ],
     *    [
     *        'id' => 'myID2',
     *        'firstname' => 'Warren'
     *    ]
     * ]
     * @param string $index
     * @param array $data
     * @return array
     */
    public function partialUpdates($index, $data) 
    {
        // Convert objectID to id for Meilisearch
        foreach ($data as &$item) {
            if (isset($item['objectID']) && !isset($item['id'])) {
                $item['id'] = $item['objectID'];
                unset($item['objectID']);
            }
        }
        
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->updateDocuments($data);
    }
            
    /**
     * Delete objects by filter in index
     * @param string $index
     * @param string $filter Filter expression
     * @return array
     */
    public function deleteBy($index, $filter = '') 
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        if (empty($filter)) {
            return $indexInstance->deleteAllDocuments();
        }
        
        // Meilisearch uses deleteDocuments with filter
        return $indexInstance->deleteDocuments([], ['filter' => $filter]);
    }
    
    /**
     * Delete all objects in index
     * @param string $index
     * @param string $query (unused, kept for compatibility)
     * @return array
     */
    public function clearObjects($index, $query = '') 
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->deleteAllDocuments();
    } 
    
    /**
     * Update/add multiple objects in index
     * @param string $index
     * @param array $data Array of documents
     * @return array
     */
    public function updates($index, $data)
    {
        // Convert objectID to id for Meilisearch
        foreach ($data as &$item) {
            if (isset($item['objectID']) && !isset($item['id'])) {
                $item['id'] = $item['objectID'];
                unset($item['objectID']);
            }
        }
        
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->addDocuments($data);
    }
    
    /**
     * Update/add object in index
     * @param string $index
     * @param array $data
     * @return array
     */
    public function update($index, $data) 
    {
        // Meilisearch uses 'id' instead of 'objectID'
        if (isset($data['objectID']) && !isset($data['id'])) {
            $data['id'] = $data['objectID'];
            unset($data['objectID']);
        }
        
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->addDocuments([$data]);
    }
    
    /**
     * Delete object from index
     * @param string $index
     * @param string|int $objectID
     * @return array
     */
    public function delete($index, $objectID) 
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->deleteDocument($objectID);
    }
    
    /**
     * Delete multiple objects from index
     * @param string $index
     * @param array $objectIDs
     * @param array $requestOptions (unused, kept for compatibility)
     * @return array
     */
    public function deleteObjects($index, $objectIDs, $requestOptions = []) 
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->deleteDocuments($objectIDs);
    }     
    
    /**
     * Search objects from index
     * @param string $index
     * @param string $query Search query
     * @param array $params Search parameters (filters, pagination, etc.)
     * @return array
     */
    public function search($index, $query, $params = []) 
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        // Map Algolia-style params to Meilisearch format
        $meiliParams = $this->mapSearchParams($params);
        
        $searchResult = $indexInstance->search($query, $meiliParams);
        
        // Get estimated total hits (capped at 1000 by Meilisearch default)
        $estimatedTotalHits = $searchResult->getEstimatedTotalHits();
        
        // If estimated total is exactly 1000, it might be capped
        // Try to get exact total for unfiltered searches
        $exactTotal = null;
        if ($estimatedTotalHits == 1000 && empty($params['filters']) && empty($query)) {
            // For unfiltered, empty query searches, get exact count from index stats
            try {
                $exactTotal = $this->getTotalCount($index);
            } catch (\Exception $e) {
                // If stats fail, fall back to estimated
                Yii::warning('Failed to get exact total from index stats: ' . $e->getMessage());
            }
        }
        
        // Convert SearchResult object to array for compatibility
        // Meilisearch PHP client returns a SearchResult object, not an array
        $result = [
            'hits' => $searchResult->getHits(),
            'estimatedTotalHits' => $estimatedTotalHits,
            'processingTimeMs' => $searchResult->getProcessingTimeMs(),
            'query' => $searchResult->getQuery(),
        ];
        
        // Add exact total if available
        if ($exactTotal !== null) {
            $result['exactTotalHits'] = $exactTotal;
        }
        
        return $result;
    }
    
    /**
     * Get exact total document count for an index
     * Useful when estimatedTotalHits is capped at 1000
     * Uses Meilisearch HTTP API directly to get index stats
     * 
     * @param string $index Index name
     * @return int Total number of documents in index
     */
    public function getTotalCount($index)
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        try {
            // Use the index stats() method to get total document count
            $stats = $indexInstance->stats();
            
            // stats() returns an array with numberOfDocuments
            return isset($stats['numberOfDocuments']) ? (int)$stats['numberOfDocuments'] : 0;
        } catch (\Exception $e) {
            Yii::error('Failed to get total count for index ' . $index . ': ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get object from given index 
     * @param string $index
     * @param string|int $objectID
     * @return array
     */
    public function getObject($index, $objectID) 
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->getDocument($objectID);
    }  
    
    /**
     * Get multiple objects from given index
     * @param string $index
     * @param array $objectIDs
     * @return array
     */
    public function getObjects($index, $objectIDs)
    {
        $client = $this->getClient();
        $indexInstance = $client->index($index);
        
        return $indexInstance->getDocuments(['ids' => $objectIDs]);
    } 

    /**
     * Initialize index (for compatibility with Algolia interface)
     * @param string $index
     * @return \MeiliSearch\Endpoints\Indexes
     */
    public function initIndex($index) 
    {
        $client = $this->getClient();
        return $client->index($index);
    }
    
    /**
     * Get Meilisearch client instance
     * @return \MeiliSearch\Client
     */
    public function getClient() 
    {
        if ($this->_client === null) {
            // Get host and masterKey, checking params if not set in component config
            $host = $this->host;
            if (empty($host) && isset(\Yii::$app->params['meilisearch_host'])) {
                $host = \Yii::$app->params['meilisearch_host'];
            }
            if (empty($host)) {
                $host = 'http://meilisearch:7700'; // default
            }
            
            $masterKey = $this->masterKey;
            if (empty($masterKey) && isset(\Yii::$app->params['meilisearch_master_key'])) {
                $masterKey = \Yii::$app->params['meilisearch_master_key'];
            }
            
            if (empty($host)) {
                throw new \Exception('Meilisearch host is not configured');
            }
            if (empty($masterKey)) {
                throw new \Exception('Meilisearch master key is not configured');
            }
            
            $this->_client = new MeiliSearchClient($host, $masterKey);
        }
        
        return $this->_client;
    }
    
    /**
     * Map Algolia-style search parameters to Meilisearch format
     * @param array $params Algolia parameters
     * @return array Meilisearch parameters
     */
    private function mapSearchParams($params)
    {
        $meiliParams = [];
        
        // Pagination
        if (isset($params['page'])) {
            $hitsPerPage = isset($params['hitsPerPage']) ? $params['hitsPerPage'] : 20;
            $meiliParams['offset'] = $params['page'] * $hitsPerPage;
        }
        if (isset($params['hitsPerPage'])) {
            $meiliParams['limit'] = $params['hitsPerPage'];
        }
        
        // Filters - Meilisearch uses a different filter syntax
        $filters = [];
        
        // New filter format: { "field": ["value1", "value2"], ... }
        if (isset($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $values) {
                if (!is_array($values) || empty($values)) {
                    continue;
                }
                
                // Handle nested fields (e.g., "store.store_id")
                $fieldParts = explode('.', $field);
                $meiliField = $field;
                
                // Build OR filter for multiple values
                $orFilters = [];
                foreach ($values as $value) {
                    // Handle different value types
                    if (is_numeric($value)) {
                        $orFilters[] = $meiliField . ' = ' . $value;
                    } else {
                        $orFilters[] = $meiliField . ' = "' . addslashes($value) . '"';
                    }
                }
                
                if (count($orFilters) > 1) {
                    $filters[] = '(' . implode(' OR ', $orFilters) . ')';
                } else {
                    $filters[] = $orFilters[0];
                }
            }
        }
        
        // Legacy Algolia format support (for backward compatibility)
        // Facet filters (AND logic)
        if (isset($params['facetFilters']) && is_array($params['facetFilters'])) {
            foreach ($params['facetFilters'] as $filter) {
                if (is_array($filter)) {
                    // Multiple values for same facet (OR within this group)
                    $orFilters = [];
                    foreach ($filter as $f) {
                        $orFilters[] = $this->parseFilter($f);
                    }
                    if (count($orFilters) > 1) {
                        $filters[] = '(' . implode(' OR ', $orFilters) . ')';
                    } else {
                        $filters[] = $orFilters[0];
                    }
                } else {
                    $filters[] = $this->parseFilter($filter);
                }
            }
        }
        
        // Disjunctive facets (OR logic across different facets)
        if (isset($params['disjunctiveFacetsRefinements']) && is_array($params['disjunctiveFacetsRefinements'])) {
            foreach ($params['disjunctiveFacetsRefinements'] as $facet => $values) {
                if (is_array($values) && count($values) > 0) {
                    $orFilters = [];
                    foreach ($values as $value) {
                        $orFilters[] = $facet . ' = "' . addslashes($value) . '"';
                    }
                    if (count($orFilters) > 1) {
                        $filters[] = '(' . implode(' OR ', $orFilters) . ')';
                    } else {
                        $filters[] = $orFilters[0];
                    }
                }
            }
        }
        
        // Numeric refinements
        if (isset($params['numericRefinements']) && is_array($params['numericRefinements'])) {
            foreach ($params['numericRefinements'] as $attribute => $operators) {
                foreach ($operators as $operator => $value) {
                    $filters[] = $attribute . ' ' . $operator . ' ' . $value;
                }
            }
        }
        
        // Tag refinements
        if (isset($params['tagRefinements']) && is_array($params['tagRefinements'])) {
            foreach ($params['tagRefinements'] as $tag) {
                $filters[] = '_tags = "' . addslashes($tag) . '"';
            }
        }
        
        // Attributes to retrieve
        if (isset($params['attributesToRetrieve'])) {
            $meiliParams['attributesToRetrieve'] = $params['attributesToRetrieve'];
        }
        
        // Combine all filters
        if (!empty($filters)) {
            $meiliParams['filter'] = implode(' AND ', $filters);
        }
        
        // Geo search - proximity filtering (add to existing filters)
        if (isset($params['geo']) && is_array($params['geo'])) {
            $geo = $params['geo'];
            if (isset($geo['lat']) && isset($geo['lng']) && isset($geo['radius'])) {
                $radius = $this->convertRadiusToMeters($geo['radius'], isset($geo['unit']) ? $geo['unit'] : 'm');
                $geoFilter = $this->buildGeoFilter($geo['lat'], $geo['lng'], $radius);
                if ($geoFilter) {
                    // Combine with existing filters
                    if (isset($meiliParams['filter'])) {
                        $meiliParams['filter'] = $meiliParams['filter'] . ' AND ' . $geoFilter;
                    } else {
                        $meiliParams['filter'] = $geoFilter;
                    }
                }
            }
        }
        
        // Geo sorting - distance-based sorting
        if (isset($params['sort']) && is_array($params['sort'])) {
            $meiliSorts = [];
            foreach ($params['sort'] as $sort) {
                // Check if it's a geo sort: _geoPoint(lat, lng):asc
                if (preg_match('/_geoPoint\(([\d.]+),\s*([\d.]+)\):(asc|desc)/i', $sort, $matches)) {
                    $lat = (float)$matches[1];
                    $lng = (float)$matches[2];
                    $direction = strtolower($matches[3]);
                    $meiliSorts[] = $this->buildGeoSort($lat, $lng, $direction);
                } else {
                    // Regular sort
                    $meiliSorts[] = $sort;
                }
            }
            if (!empty($meiliSorts)) {
                $meiliParams['sort'] = $meiliSorts;
            }
        } elseif (isset($params['sort']) && is_string($params['sort'])) {
            // Single sort string
            if (preg_match('/_geoPoint\(([\d.]+),\s*([\d.]+)\):(asc|desc)/i', $params['sort'], $matches)) {
                $lat = (float)$matches[1];
                $lng = (float)$matches[2];
                $direction = strtolower($matches[3]);
                $meiliParams['sort'] = [$this->buildGeoSort($lat, $lng, $direction)];
            } else {
                $meiliParams['sort'] = [$params['sort']];
            }
        }
        
        // Sorting
        if (isset($params['sortFacetValuesBy'])) {
            // Meilisearch doesn't have exact equivalent, but we can use sort
            // This would need custom handling
        }
        
        return $meiliParams;
    }
    
    /**
     * Build Meilisearch geo radius filter
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param int $radiusInMeters Radius in meters
     * @return string Meilisearch filter string
     */
    public function buildGeoFilter($lat, $lng, $radiusInMeters)
    {
        if (!$lat || !$lng || !$radiusInMeters) {
            return null;
        }
        // Meilisearch geo filter format: _geoRadius(lat, lng, radius_in_meters)
        return "_geoRadius({$lat}, {$lng}, {$radiusInMeters})";
    }
    
    /**
     * Build Meilisearch geo sort
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $direction 'asc' or 'desc'
     * @return string Meilisearch sort string
     */
    public function buildGeoSort($lat, $lng, $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        // Meilisearch geo sort format: _geoPoint(lat, lng):asc
        return "_geoPoint({$lat}, {$lng}):{$direction}";
    }
    
    /**
     * Convert radius to meters
     * @param int|float $radius
     * @param string $unit 'm', 'km', 'miles'
     * @return int Radius in meters
     */
    private function convertRadiusToMeters($radius, $unit = 'm')
    {
        $unit = strtolower($unit);
        switch ($unit) {
            case 'km':
                return (int)($radius * 1000);
            case 'miles':
                return (int)($radius * 1609.34);
            case 'm':
            default:
                return (int)$radius;
        }
    }
    
    /**
     * Search with geo proximity
     * @param string $index
     * @param string $query
     * @param array $params
     * @param array|null $geoPoint {lat: float, lng: float}
     * @param int|null $radiusInMeters
     * @return array
     */
    public function searchWithGeo($index, $query, $params = [], $geoPoint = null, $radiusInMeters = null)
    {
        if ($geoPoint && isset($geoPoint['lat']) && isset($geoPoint['lng']) && $radiusInMeters) {
            $params['geo'] = [
                'lat' => $geoPoint['lat'],
                'lng' => $geoPoint['lng'],
                'radius' => $radiusInMeters,
                'unit' => 'm'
            ];
        }
        return $this->search($index, $query, $params);
    }
    
    /**
     * Calculate distance between two points (Haversine formula)
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in meters
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Parse a filter string into Meilisearch filter format
     * Supports formats like: "field:value", "field=value", etc.
     * @param string $filter
     * @return string
     */
    private function parseFilter($filter)
    {
        // Handle common formats
        if (strpos($filter, ':') !== false) {
            list($field, $value) = explode(':', $filter, 2);
            return trim($field) . ' = "' . addslashes(trim($value)) . '"';
        }
        
        // If already in Meilisearch format, return as is
        return $filter;
    }
}

