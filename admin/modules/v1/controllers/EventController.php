<?php

namespace admin\modules\v1\controllers;


use admin\models\TransferCandidate;
use company\models\TranferExcel;
use Yii;
use yii\base\Exception;
use yii\rest\Controller;
use Segment\Segment;

/**
 * Transfer controller - Manage Events
 */
class EventController extends Controller
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
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options', 'text'];

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
     * import excel to extract event data
     * @return type
     */
    public function actionImportExcel() {

        $event = Yii::$app->request->getBodyParam('event');

        if(empty($event)) {
            return [
                "operation" => "error",
                "message" => "Event name can not be empty"
            ];
        }

        if(YII_ENV != 'prod') {
            return [
                "operation" => "error",
                "message" => "This feature is only for production server"
            ];
        }

        $restrictedEvents = [
            "Request Activity Added",
            "Transfer Created",
            "Transfer Updated",
            "Fulltimer Created",
            "Fulltimer Updated",
            "Request Created",
            "Request Updated",
            "Suggestion Created",
            "Suggestion Updated",
            "Transfer Marked As Payment Received",
            "Transfer Locked",
            "Transfer UnLocked",
            "Candidate Transfer Paid",
            "Candidate Profile Created",
            "Candidate Profile Updated",
            "Candidate Invitation Accepted",
            "Candidate Invitation Rejected",
            "Candidate Invited"
        ];

        if(in_array($event, $restrictedEvents)) {
            return [
                "operation" => "error",
                "message" => "This event can not be fired manually"
            ];
        }

        $model = new TranferExcel;
        $model->excel = Yii::$app->request->getBodyParam('excel');

        if(!$model->validate())
        {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => $model->getErrors()
            ];
        }

        $fileUrl = Yii::$app->temporaryBucketResourceManager->getUrl($model->excel);

        //save in temp folder to process

        $tmpFile = sys_get_temp_dir() . '/' . $model->excel;

        if(!file_put_contents($tmpFile, file_get_contents($fileUrl))) {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];
        }

        $excelData  = \moonland\phpexcel\Excel::import(sys_get_temp_dir() . '/' . $model->excel,  [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row

        //\yii\helpers\ArrayHelper::remove($excelData, '1');

        // row will be key

        $keys = \yii\helpers\ArrayHelper::remove($excelData, '1');
        
        if(empty($keys["A"])) {
            return [
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];
        }

        //create array with key to read data

        $data = [];

        foreach ($excelData as $values)
        {
            $data[] = array_combine($keys, $values);
        }

        //no need file anymore

        @unlink($tmpFile);

        $total = 0;

        $candidatesTransfers = [];

        foreach ($data as $key => $value)
        {
            //if empty cell, ignore

            if(empty($value[$keys["A"]])) {
                continue;
            }

            $datetime = isset($value['Datetime'])?
                    new \DateTime(strtotime($value['Datetime'])): new \DateTime();

//            Segment::track([
//                'userId' => Yii::$app->user->getId(),
//                'event' => $event,
//                'properties' => $value,
//                'timestamp' => $datetime->format('c')
//            ]);

            $total++;
        }

        Segment::flush();

        return [
            'total' => $total,
            "message" => $total . " total events fired",
            'operation' => "success"
        ];
    }
}
