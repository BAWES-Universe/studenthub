<?php

namespace company\modules\v1\controllers;

use company\models\Request;
use Yii;

/**
 * Algolia controller
 */
class AlgoliaController extends BaseController
{
    /**
     * Return auto disposable secure api key
     * Supports both Algolia and Meilisearch based on config
     */
    public function actionKey()
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $activeRequests = Request::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->activeRequest()
            ->count();

        if(!$activeRequests) {
            return [
                'operation' => 'error',
                'message' => 'No Active Request'
            ];
        }

        // Check if Meilisearch should be used (default to Algolia for backward compatibility)
        $useMeilisearch = isset(Yii::$app->params['use_meilisearch']) && Yii::$app->params['use_meilisearch'] === true;
        
        if ($useMeilisearch) {
            return $this->getMeilisearchKey();
        }
        
        return $this->getAlgoliaKey();
    }
    
    /**
     * Get Algolia key (legacy)
     */
    private function getAlgoliaKey()
    {
        $ttl = 60 * 2; //2 min

        $params = [
            'restrictIndices' => [
                Yii::$app->params['algolia_candidate_index']
            ],
            //'filters' => 'assigned=0 AND ',
            'facetFilters' => [
                'candidate_committed:Yes',
                'assigned:0',
            ],
            'validUntil' => time() + $ttl,
            'userToken' => Yii::$app->user->getId(),
           // 'getRankingInfo' => true,
           // 'aroundLatLngViaIP' => true,
           // 'aroundRadius' => 'all'
        ];

        $securedApiKey = Yii::$app->algolia->getSecureApiKey($params);

        return [
            'securedApiKey' => $securedApiKey,
            'securedApiKeyValidUntil' => $params['validUntil'],
            'appId' => Yii::$app->algolia->appId
        ];
    }
    
    /**
     * Get Meilisearch key
     */
    private function getMeilisearchKey()
    {
        $ttl = 60 * 2; // 2 minutes
        
        // Get index name
        $candidateIndex = isset(Yii::$app->params['meilisearch_candidate_index']) 
            ? Yii::$app->params['meilisearch_candidate_index'] 
            : Yii::$app->params['algolia_candidate_index'];
        
        // Generate temporary search key with restrictions
        $keyParams = [
            'indexes' => [$candidateIndex],
            'expiresAt' => time() + $ttl,
            'description' => 'Temporary search key for company user ' . Yii::$app->user->getId()
        ];
        
        try {
            $temporaryKey = Yii::$app->meilisearch->generateSearchKey($keyParams);
            $host = isset(Yii::$app->params['meilisearch_host']) 
                ? Yii::$app->params['meilisearch_host'] 
                : 'http://meilisearch:7700';
            
            return [
                'host' => $host,
                'apiKey' => $temporaryKey,
                'apiKeyValidUntil' => time() + $ttl,
                // For backward compatibility with frontend
                'securedApiKey' => $temporaryKey,
                'securedApiKeyValidUntil' => time() + $ttl,
                'appId' => null
            ];
        } catch (\Exception $e) {
            Yii::error('Failed to generate Meilisearch key: ' . $e->getMessage());
            // Fallback to Algolia on error
            return $this->getAlgoliaKey();
        }
    }
    
    /**
     * Search proxy endpoint - accepts Algolia-compatible requests and translates to Meilisearch
     * POST /v1/algolia/search or /v1/meilisearch/search
     */
    public function actionSearch()
    {
        $useMeilisearch = isset(Yii::$app->params['use_meilisearch']) && Yii::$app->params['use_meilisearch'] === true;
        
        if (!$useMeilisearch) {
            throw new \yii\web\BadRequestHttpException('Meilisearch is not enabled');
        }
        
        $request = Yii::$app->request;
        $body = json_decode($request->getRawBody(), true);
        
        if (!isset($body['indexName']) || !isset($body['params'])) {
            throw new \yii\web\BadRequestHttpException('Missing indexName or params');
        }
        
        $indexName = $body['indexName'];
        $searchParams = $body['params'];
        
        // Extract query
        $query = isset($searchParams['query']) ? $searchParams['query'] : '';
        
        // Perform search
        try {
            $result = Yii::$app->meilisearch->search($indexName, $query, $searchParams);
            
            // Transform Meilisearch response to Algolia format
            $hitsPerPage = isset($searchParams['hitsPerPage']) ? $searchParams['hitsPerPage'] : 20;
            $page = isset($searchParams['page']) ? $searchParams['page'] : 0;
            
            // Convert objectID back from id for compatibility
            foreach ($result['hits'] as &$hit) {
                if (isset($hit['id']) && !isset($hit['objectID'])) {
                    $hit['objectID'] = $hit['id'];
                }
            }
            
            $nbHits = isset($result['estimatedTotalHits']) ? $result['estimatedTotalHits'] : count($result['hits']);
            $nbPages = ceil($nbHits / $hitsPerPage);
            $processingTimeMS = isset($result['processingTimeMs']) ? $result['processingTimeMs'] : 0;
            
            return [
                'results' => [[
                    'hits' => $result['hits'],
                    'nbHits' => $nbHits,
                    'nbPages' => $nbPages,
                    'page' => $page,
                    'processingTimeMS' => $processingTimeMS,
                    'query' => $query
                ]]
            ];
        } catch (\Exception $e) {
            Yii::error('Meilisearch search error: ' . $e->getMessage());
            throw new \yii\web\ServerErrorHttpException('Search failed: ' . $e->getMessage());
        }
    }
}
