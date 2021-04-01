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
}
