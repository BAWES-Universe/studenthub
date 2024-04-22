<?php

namespace manager\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use company\models\Store;
use company\models\Company;

/**
 * Store controller - Manage store as manager
 */
class StoreController extends BaseController
{
    public function actionView()
    {
        $store = Yii::$app->user->identity
            ->getStore()->one();

        if (!$store)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $store;
    }
}
