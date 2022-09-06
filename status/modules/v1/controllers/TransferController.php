<?php

namespace status\modules\v1\controllers;

use Yii;
use admin\models\Transfer;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class TransferController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'filename',
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

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
            'collectionOptions' => ['POST'],
            'resourceOptions' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Transfer.
     */
    public function actionList()
    {
        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');
        $suspicious = Yii::$app->request->get('suspicious');

        $query = Transfer::find()
            ->isParentTransfer();

        if ($company_name) {
            $query->companyJoin()
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($suspicious) {
            $query->filterSuspicious();
        }

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at DESC');

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return Transfer detail.
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id)
    {
        $transfer = Transfer::find()
            ->with([
                'transferCandidates',
                'transferCandidates.candidate',
                //    'transferCandidates.candidate.store',
                //    'transferCandidates.candidate.company',
                //    'transferCandidates.candidate.bank',
                //    'transferCandidates.candidate.university'
            ])
            ->andWhere([
                'transfer_id' => $id
            ])
            ->one();

        if(!$transfer) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $transfer;
    }

    /**
     * @return mixed|ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionSuspiciousList()
    {
        $company_name = Yii::$app->request->get('company_name');
        $transfer_status = Yii::$app->request->get('transfer_status');
        $start_date = Yii::$app->request->get('start_date');
        $end_date = Yii::$app->request->get('end_date');

        $query = Transfer::find()
            ->joinWith([
                'transferCandidates' => function($query) {
                    return $query
                        ->joinWith('candidate')
                        ->andWhere('`candidate`.`store_id` = `transfer_candidate`.`store_id` ')
                        ->andWhere('`transfer_candidate`.`candidate_hourly_rate` != `candidate`.`candidate_hourly_rate`');

                }
            ]);

        $query->isParentTransfer();

        if ($company_name) {
            $query->companyJoin()
                ->filterCompany($company_name);
        }

        if($transfer_status)
            $query->filterStatus($transfer_status);

        if($start_date)
            $query->startDate($start_date);

        if($end_date)
            $query->endDate($end_date);

        $query->groupBy('{{%transfer}}.transfer_id');
        $query->orderBy('{{%transfer}}.transfer_updated_at DESC');

//        return $query->createCommand()->getRawSql();
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Finds the Transfer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Transfer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}