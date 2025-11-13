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
     * Get total document count for an index
     * Useful when estimatedTotalHits is capped at 1000
     * GET /v1/meilisearch/total-count?indexName=dev_candidate_public
     */
    public function actionTotalCount()
    {
        $indexName = Yii::$app->request->get('indexName');
        
        if (empty($indexName)) {
            throw new \yii\web\BadRequestHttpException('Missing indexName parameter');
        }
        
        try {
            $total = Yii::$app->meilisearch->getTotalCount($indexName);
            return [
                'total' => $total,
                'indexName' => $indexName
            ];
        } catch (\Exception $e) {
            Yii::error('Failed to get total count: ' . $e->getMessage());
            throw new \yii\web\ServerErrorHttpException('Failed to get total count: ' . $e->getMessage());
        }
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
     * 
     * Request format:
     * {
     *   "indexName": "dev_candidate_public",
     *   "query": "search term",
     *   "filters": {"candidate_gender": ["Male", "Female"], ...},
     *   "geo": {"lat": 29.3759, "lng": 47.9774, "radius": 10000, "unit": "m"},
     *   "sort": ["_geoPoint(29.3759, 47.9774):asc", "candidate_updated_at_timestamp:desc"],
     *   "page": 0,
     *   "hitsPerPage": 20,
     *   "facets": ["candidate_gender", "candidate_driving_license", ...]
     * }
     */
    public function actionSearch()
    {
        $request = Yii::$app->request;
        $body = json_decode($request->getRawBody(), true);
        
        if (!isset($body['indexName'])) {
            throw new \yii\web\BadRequestHttpException('Missing indexName');
        }
        
        $indexName = $body['indexName'];
        
        // Extract query (optional)
        $query = isset($body['query']) ? $body['query'] : '';
        
        // Build search parameters
        $searchParams = [];
        
        // Filters
        if (isset($body['filters']) && is_array($body['filters'])) {
            $searchParams['filters'] = $body['filters'];
        }
        
        // Geo search
        if (isset($body['geo']) && is_array($body['geo'])) {
            $searchParams['geo'] = $body['geo'];
        }
        
        // Sorting
        if (isset($body['sort'])) {
            $searchParams['sort'] = $body['sort'];
        }
        
        // Pagination
        $page = isset($body['page']) ? (int)$body['page'] : 0;
        $hitsPerPage = isset($body['hitsPerPage']) ? (int)$body['hitsPerPage'] : 20;
        $searchParams['page'] = $page;
        $searchParams['hitsPerPage'] = $hitsPerPage;
        
        // Facets (for real-time counts)
        $requestedFacets = [];
        if (isset($body['facets']) && is_array($body['facets'])) {
            $requestedFacets = $body['facets'];
            $searchParams['facets'] = $body['facets'];
        }
        
        // Perform search
        try {
            $result = Yii::$app->meilisearch->search($indexName, $query, $searchParams);
            
            // Get geo point for distance calculation
            $geoPoint = null;
            if (isset($body['geo']) && isset($body['geo']['lat']) && isset($body['geo']['lng'])) {
                $geoPoint = [
                    'lat' => (float)$body['geo']['lat'],
                    'lng' => (float)$body['geo']['lng']
                ];
            }
            
            // Transform hits: add objectID, calculate distance if geo search
            $transformedHits = [];
            foreach ($result['hits'] as $hit) {
                $transformedHit = $hit;
                
                // Convert id to objectID for compatibility
                if (isset($hit['id']) && !isset($hit['objectID'])) {
                    $transformedHit['objectID'] = $hit['id'];
                }
                if (isset($hit['candidate_id']) && !isset($transformedHit['objectID'])) {
                    $transformedHit['objectID'] = $hit['candidate_id'];
                }
                
                // Calculate distance if geo search is active
                if ($geoPoint && isset($hit['_geo']['lat']) && isset($hit['_geo']['lng'])) {
                    $distance = Yii::$app->meilisearch->calculateDistance(
                        $geoPoint['lat'],
                        $geoPoint['lng'],
                        $hit['_geo']['lat'],
                        $hit['_geo']['lng']
                    );
                    $transformedHit['_geoDistance'] = round($distance, 2); // meters, rounded to 2 decimals
                }
                
                $transformedHits[] = $transformedHit;
            }
            
            // Get facets (real-time counts)
            $facets = isset($result['facets']) ? $result['facets'] : [];
            
            // Ensure all requested facets are present (even if empty)
            foreach ($requestedFacets as $facet) {
                if (!isset($facets[$facet])) {
                    $facets[$facet] = [];
                }
            }
            
            // Build response
            // Use exact total if available (for unfiltered searches), otherwise use estimated
            $total = isset($result['exactTotalHits']) ? $result['exactTotalHits'] : 
                     (isset($result['estimatedTotalHits']) ? $result['estimatedTotalHits'] : count($transformedHits));
            
            // If estimated is exactly 1000 and we have filters/query, it might be capped
            // For filtered searches, we can't get exact total efficiently, so use estimated
            $isEstimated = !isset($result['exactTotalHits']) && 
                          isset($result['estimatedTotalHits']) && 
                          $result['estimatedTotalHits'] == 1000 &&
                          (!empty($body['filters']) || !empty($query));
            
            $totalPages = ceil($total / $hitsPerPage);
            $processingTimeMs = isset($result['processingTimeMs']) ? $result['processingTimeMs'] : 0;
            
            return [
                'hits' => $transformedHits,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'hitsPerPage' => $hitsPerPage,
                    'totalPages' => $totalPages,
                    'isEstimated' => $isEstimated,  // Indicates if total might be capped
                ],
                'facets' => $facets,
                'processingTimeMs' => $processingTimeMs,
                'query' => $query,
            ];
        } catch (\Exception $e) {
            Yii::error('Meilisearch search error: ' . $e->getMessage());
            Yii::error('Search params: ' . json_encode($searchParams));
            throw new \yii\web\ServerErrorHttpException('Search failed: ' . $e->getMessage());
        }
    }
}
