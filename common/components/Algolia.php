<?php
namespace common\components;

/**
 * $result = Yii::$app->algolia->search('getstarted_actors', 'cat');
 */
class Algolia {
    
    public $appId;
    
    public $apiKey;
    
    public $index;
        
    /**
     * https://www.algolia.com/doc/api-reference/api-methods/generate-secured-api-key/?language=php
     */
    public function getSecureApiKey($params = []) 
    {
        $client = $this->getClient();
        
        return $client::generateSecuredApiKey(
            $this->apiKey,//publicKey
            $params
        );
    }
      
    /**
     * Add object to index
     * @param type $index
     * @param type $data
     * @return type
     */
    public function add($index, $data)
    {
        return $this->initIndex($index)
            ->saveObject($data);
    }
    
    /**
     * Partially update objects 
     * $data =[
     *    'objectID'  => 'myID2',
     *    'firstname' => 'Warren'
     * ]
     * @param string $index
     * @param array $data
     * @return array
     */
    public function partialUpdate($index, $data) 
    {
        return $this->initIndex($index)
            ->partialUpdateObject($data, [
                'createIfNotExists' => true
            ]);
    }
    
    /**
     * Partially update objects 
     * $data =[
     *    [
     *        'objectID'  => 'myID1',
     *        'firstname' => 'Jimmie'
     *    ],
     *    [
     *        'objectID'  => 'myID2',
     *        'firstname' => 'Warren'
     *    ]
     * ]
     * @param string $index
     * @param array $data
     * @return array
     */
    public function partialUpdates($index, $data) 
    {
        return $this->initIndex($index)
            ->partialUpdateObjects($data);
    }
            
    /**
     * Delete objects by params in index
     * @param string $index
     * @return array
     */
    public function deleteBy($index, $params = []) 
    {
        return $this->initIndex($index)
            ->deleteBy($params);
    }
    
    /**
     * Delete all objects in index
     * @param string $index
     * @return array
     */
    public function clearObjects($index, $query = '') 
    {
        return $this->initIndex($index)
            ->clearObjects();
    } 
    
    /**
     * Update objects in index
     * @param string $index
     * @param array $data
     * @return array
     */
    public function updates($index, $data)
    {
	return $this->initIndex($index)
            ->saveObjects($data);
    }
    
    /**
     * Update object in index
     * @param string $index
     * @param array $data
     * @return array
     */
    public function update($index, $data) 
    {
        return $this->initIndex($index)
            ->saveObject($data);
    }
    
    /**
     * Delete object from index
     * @param string $index
     * @param string $objectID
     * @return array
     */
    public function delete($index, $objectID) 
    {
        return $this->initIndex($index)
            //->custom($method, $path)    
            ->deleteObject($objectID);
    }
    
    /**
     * Delete objects from index
     * @param string $index
     * @param string $objectIDs
     * @return type
     */
    public function deleteObjects($index, $objectIDs, $requestOptions = []) 
    {
        return $this->initIndex($index)
            ->deleteObjects($objectIDs, $requestOptions);
    }     
    
    /**
     * Search objects from index
     * @param string $index
     * @param string $query
     * @param array $params
     * @return array
     */
    public function search($index, $query, $params = []) 
    {
        return $this->initIndex($index)
            ->search($query, $params);
    }

    /**
     * get object from given index 
     * @param string $index
     * @param string $objectID
     * @return array
     */
    public function getObject($index, $objectID) 
    {
        return $this->initIndex($index)
            ->getObject($objectID); 
    }  
    
    /**
     * get objects from given index
     * @param string $index
     * @param string $objectID
     * @return array
     */
    public function getObjects($index, $objectID)
    {
        return $this->initIndex($index)
            ->getObjects($objectID);
    } 

    /**
     * Initialize index
     * @param string $index
     * @return array
     */
    public function initIndex($index) 
    {
        return $this->getClient()
            ->initIndex($index);
    }
    
    /**
     * Return algolia client to call api
     * @return \AlgoliaSearch\Client
     */
    private function getClient() 
    {
        return \Algolia\AlgoliaSearch\SearchClient::create($this->appId, $this->apiKey);
    }
}

