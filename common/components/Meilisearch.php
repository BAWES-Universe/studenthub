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
            $response = $client->createKey($keyParams);
            return $response['key'];
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
        
        return $indexInstance->search($query, $meiliParams);
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
        
        if (!empty($filters)) {
            $meiliParams['filter'] = implode(' AND ', $filters);
        }
        
        // Attributes to retrieve
        if (isset($params['attributesToRetrieve'])) {
            $meiliParams['attributesToRetrieve'] = $params['attributesToRetrieve'];
        }
        
        // Sorting
        if (isset($params['sortFacetValuesBy'])) {
            // Meilisearch doesn't have exact equivalent, but we can use sort
            // This would need custom handling
        }
        
        return $meiliParams;
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

