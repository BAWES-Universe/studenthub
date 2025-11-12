<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;


/**
 * Meilisearch controller
 */
class MeilisearchController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return temporary Meilisearch search key with restrictions
     */
    public function actionKey() 
    {
        $ttl = 60 * 2; // 2 minutes
        
        $currency = Yii::$app->request->headers->get("Currency", "KWD");
        
        // Get index names
        $candidateIndex = Yii::$app->params['meilisearch_candidate_index'];
        $fulltimerIndex = Yii::$app->params['meilisearch_fulltimer_index'];
        
        // Generate temporary search key with restrictions
        $keyParams = [
            'indexes' => [$candidateIndex, $fulltimerIndex],
            'expiresAt' => time() + $ttl,
            'description' => 'Temporary search key for user ' . Yii::$app->user->getId() . ' (currency: ' . $currency . ')'
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
