<?php

namespace company\modules\v1\controllers;

use Yii;
use common\models\Campaign;

class CampaignController extends BaseController
{
    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options', 'click'];

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
     * @param $id
     * @return array|string[]
     */
    public function actionClick($id)
    {
        $model = Campaign::find()->where([
            'utm_uuid' => $id
        ])->one();

        $model->no_of_clicks = $model->no_of_clicks + 1;

        if(!$model->save()) {
            return [
                'operation' => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success"
        ];
    }
}