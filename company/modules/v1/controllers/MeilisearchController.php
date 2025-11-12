<?php

namespace company\modules\v1\controllers;

use company\models\Request;
use Yii;

/**
 * Meilisearch controller
 */
class MeilisearchController extends BaseController
{
    /**
     * Return temporary Meilisearch search key with restrictions
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

        $ttl = 60 * 2; // 2 minutes
        
        // Get index name
        $candidateIndex = Yii::$app->params['meilisearch_candidate_index'];
        
        // Generate temporary search key with restrictions
        $keyParams = [
            'indexes' => [$candidateIndex],
            'expiresAt' => time() + $ttl,
            'description' => 'Temporary search key for company user ' . Yii::$app->user->getId()
        ];
        
        try {
            $temporaryKey = Yii::$app->meilisearch->generateSearchKey($keyParams);
            $host = Yii::$app->params['meilisearch_host'];
            
            return [
                'host' => $host,
                'apiKey' => $temporaryKey,
                'apiKeyValidUntil' => time() + $ttl
            ];
        } catch (\Exception $e) {
            Yii::error('Failed to generate Meilisearch key: ' . $e->getMessage());
            throw new \yii\web\ServerErrorHttpException('Failed to generate search key: ' . $e->getMessage());
        }
    }
    
    /**
     * Search endpoint - accepts search requests and returns results
     * POST /v1/meilisearch/search
     */
    public function actionSearch()
    {
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
            
            // Transform Meilisearch response to frontend-compatible format
            $hitsPerPage = isset($searchParams['hitsPerPage']) ? $searchParams['hitsPerPage'] : 20;
            $page = isset($searchParams['page']) ? $searchParams['page'] : 0;
            
            // Convert id to objectID for compatibility
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
