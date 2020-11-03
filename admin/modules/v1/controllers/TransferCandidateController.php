<?php

namespace admin\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use admin\models\Transfer;
use admin\models\TransferCandidate;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Transfer controller - Manage Transfer
 */
class TransferCandidateController extends Controller
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

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options','text'];

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
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionList()
    {
        $tc_id = Yii::$app->request->get('tc_id');

        $transfer_confirmation_id = Yii::$app->request->get('transfer_confirmation_id');

        $query = TransferCandidate::find()
            ->with('candidate')
            ->payableWithPaid();
        
        if($tc_id) 
        {
            $transferCandidateRecords = array_diff(explode(",", $tc_id),[""]);
    
            $query->andWhere(['in', 'tc_id', $transferCandidateRecords]);
        }
        
        if($transfer_confirmation_id) {
            $query->andWhere(['transfer_confirmation_id' => $transfer_confirmation_id]);
        }
        
        return new \yii\data\ActiveDataProvider([
            'pagination' => null,
            'query' => $query
        ]);
    }

    /**
     * load candidate transfer entries by transfer id
     * @param number $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionByTransfer($id) 
    {
        $transfer = $this->findTransfer($id);
        
        $query = $transfer->getTransferCandidates()
            ->orderBy('store_id');//to group it by store on infinite scrolling listing 
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * load candidate transfer entries by transfer file id
     * @param number $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionByTransferFile($id) 
    {
        $transfer = $this->findTransferFile($id);
        
        $query = $transfer->getTransferCandidates()
            ->orderBy('store_id');//to group it by store on infinite scrolling listing 
        
        return new \yii\data\ActiveDataProvider([
            'query' => $query
        ]);
    }
    
    /**
     * view candidate transfer detail
     * @param type $id
     * @return type
     */
    public function actionView($id)
    { 
        $model =  TransferCandidate::find()
            ->with('candidate')
            ->andWhere(['tc_id' => $id])
            ->payableWithPaid()
            ->one();
         
        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        
        return $model;
    }

    /**
     * @param $id
     * @return array
     */
    public function actionUnpaid($id)
    {
        return TransferCandidate::markUnpaid($id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function actionPaid($id)
    {
        $transfer_confirmation_id = Yii::$app->request->getBodyParam('transfer_confirmation_id');
        
        return TransferCandidate::markPaid($id, $transfer_confirmation_id);
    }

    /**
     * @return array
     */
    public function actionMarkPaidAll()
    {
        $transferCandidateIds = Yii::$app->request->getBodyParam('transferCandidate');
        return TransferCandidate::markAllPaid($transferCandidateIds);
    }

    /**
     * @return array
     */
    public function actionMarkUnpaidAll()
    {
        $transferCandidateIds = Yii::$app->request->getBodyParam('transferCandidate');
        return TransferCandidate::markAllUnpaid($transferCandidateIds);
    }
    
    /**
     * Finds the Transfer file model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return TransferFile the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTransferFile($id)
    {
        if (($model = \common\models\TransferFile::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    /**
     * Finds the Transfer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTransfer($id)
    {
        if (($model = Transfer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
