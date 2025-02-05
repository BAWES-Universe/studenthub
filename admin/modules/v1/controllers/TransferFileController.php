<?php

namespace admin\modules\v1\controllers;

use admin\models\Transfer;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;
use admin\models\TransferFile;


/**
 * Manage transfer files
 *
 * @author krushn
 */
class TransferFileController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
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
            'class' => HttpBearerAuth::class,
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
     * Return a List of Transfer Files.
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $query = TransferFile::find()
           // ->innerJoinWith(['transferCandidates'])
            ->groupBy('transfer_file_id')
            ->orderBy(new \yii\db\Expression('transfer_file_created_at DESC'));

        if($currency) {
            $query->andWhere(['transfer_file.currency_code' => $currency]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionReSchedule($id) {
        $model = $this->findModel($id);

        if($model->status != TransferFile::STATUS_FAILED) {
            return [
                "operation" => "error",
                "message" => "Only failed file can be re-scheduled!"
            ];
        }

        $model->status = TransferFile::STATUS_PENDING;

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Done"
        ];
    }

    /**
     * load transfer file details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Finds the Transfer file model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TransferFile the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = TransferFile::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}