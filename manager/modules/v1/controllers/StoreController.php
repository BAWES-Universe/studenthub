<?php

namespace manager\modules\v1\controllers;

use Yii;

/**
 * Store controller - Manage store as manager
 */
class StoreController extends BaseController
{
    public function actionView()
    {
        $store = Yii::$app->user->identity
            ->getStore()->one();

        if (!$store) {
            throw new \yii\web\UnauthorizedHttpException('The requested page does not exist.');
        }

        return $store;
    }
}
