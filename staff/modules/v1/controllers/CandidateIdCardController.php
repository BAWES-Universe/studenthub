<?php

namespace staff\modules\v1\controllers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QROutputInterface;
use staff\models\Note;
use staff\models\Staff;
//use Da\QrCode\QrCode;
use Yii;
use yii\helpers\ArrayHelper; 
use yii\web\NotFoundHttpException;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\Candidate;
use staff\models\CandidateIdCard;
use common\models\CandidateIdRequest;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * CandidateIdcard controller - Manage Candidate ID as Staff
 */
class CandidateIdCardController extends Controller
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
                'Access-Control-Allow-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count',
                    'Content-Range',
                    'Pragma',
                    'Expires',
                    'Cache-Control',
                    'Content-Disposition',
                    'Content-Type',
                    'Content-Length',
                    'Location',
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options', 'view'];

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
     * View ID card detail
     */
    public function actionView($id, $token)
    {
        if (!$this->loginByAccessToken($token)) {
            throw new \yii\web\ForbiddenHttpException('Invalid Access');
        }

        $side = Yii::$app->request->get('side');

        $model = $this->findModel($id);

        Yii::$app->response->format = yii\web\Response::FORMAT_HTML;
        
        $qrCode = null;
        
        if ($model->candidate->candidate_uid) {
            //$writer = new \Da\QrCode\Writer\JpgWriter();

            $path = (YII_ENV == 'prod') ? "https://v.studenthub.co/" : "https://v.dev.studenthub.co/";

            $options = new QROptions(
                [
                    'eccLevel' => EccLevel::L,// QRCode::ECC_L,
                   // 'outputType' => QROutputInterface::MARKUP_SVG,
                    'outputInterface' => QRGdImagePNG::class,
                    'version' => 7,
                ]
            );

            $qrCode = (new QRCode($options))
                ->render($path . $model->candidate->candidate_uid);

            /*$qrCode = (new QrCode($path . $model->candidate->candidate_uid, null, $writer))
                ->setSize(500)
                ->setMargin(5);*/

         //   echo $qrcode;
           // die();
        }
        
        return $this->renderPartial('view', [
            'model' => $model,
            'qrCode' => $qrCode,
            'side' => $side
        ]);
    }

    /**
     * List candidates having ID Cards
     */
    public function actionListCandidateIds()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->joinWith('candidateIdCard', true, 'INNER JOIN');

        if($candidate_name) {
            $query->filterName($candidate_name);
        }
        $query->andWhere(['{{%candidate_id_card}}.deleted'=>0]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * List candidates to generate ID Cards
     */
    public function actionListCandidates()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->idNeedGenerated()
            ->notDeleted();

        if($candidate_name)
        {
            $query->filterName($candidate_name);
        }

        $query->filterAssigned(); // only candidate with assigned work

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Generate ID for candidates
     */
    public function actionGenerate()
    {
        $candidate_ids = [];

        //remove null values

        $a = Yii::$app->request->getBodyParam('candidates');

        if(!$a || !is_array($a)) {
            return [
                'operation' => 'error',
                "code" => 1,
                'message' => 'Invalid Candidate Ids'
            ];
        }
        
        foreach ($a as $key => $value)
        {
            if($value)
                $candidate_ids[] = $value;
        }

        if(empty(Yii::$app->params['inCodeception']))
            $transaction = Yii::$app->db->beginTransaction();

        // create ID Card entry

        foreach ($candidate_ids as $key => $value)
        {
            //check if id card already available

            $ID = CandidateIdCard::find()
                ->andWhere(['candidate_id' => $value])
                ->one();

            if(!$ID) {
                $ID = new CandidateIdCard;
                $ID->candidate_id = $value;
            }

            $ID->expiry_date = date('Y-m-d', strtotime('+3 months'));

            if(!$ID->save())
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                Yii::$app->response->statusCode = 400;

                return [
                    'operation' => 'error',
                    "code" => 2,
                    "errors" => $ID->errors,
                    'message' => 'Invalid Candidate Id #'.$value
                ];
            }

            $staffName = Yii::$app->user->identity->staff_name;

            $noteModel  = new Note();
            $noteModel->candidate_id  = $value;
            $noteModel->note_type  = Note::TYPE_INTERNAL_NOTE;
            $noteModel->note_text  = "candidate id card has been generated by {$staffName}";

            if (!$noteModel->save(false)) {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    'operation' => 'error',
                    "code" => 3,
                    "errors" => $noteModel->errors,
                    'message' => 'Error while saving note'
                ];
            }
        }

        // $model = new CandidateIdRequest();
        // $model->candidate_ids = implode(",", $candidate_ids);

        // if (!$model->save()) {
        //     if(empty(Yii::$app->params['inCodeception']))
        //         $transaction->rollBack();

        //     return [
        //         "operation" => "error",
        //         "message" => $model->errors
        //     ];
        // }

        // if(empty(Yii::$app->params['inCodeception']))
        //     $transaction->commit();

        // return [
        //     "operation" => "success",
        //     "cir_uuid" => $model->cir_uuid,
        //     "message" => "We processing your request"
        // ];
        
        //create zip file to download generated IDs

        $candidates = Candidate::find()
            ->andWhere(['in', 'candidate_id', $candidate_ids])
            ->all();

        $result = CandidateIdCard::createIdCards($candidates);

        if($result['operation'] == 'error')
            return $result;

        //log message

        $names = ArrayHelper::map($candidates, 'candidate_id', 'candidate_name');

        Yii::info('[ID Cards Generated] Candidate ID Cards for ['.implode(', ', $names).'] have been generated by '.Yii::$app->user->identity->staff_name, __METHOD__);

        if(!$result['zip']) {
            return [
                'operation' => 'error',
                "code" => 4,
                'message' => 'Error generating zip',
                'cardUrl' => Yii::$app->urlManagerStaff->createAbsoluteUrl("/candidate-id-cards/")
            ];
        }

        if(!file_exists($result['zip'])) {
            return [
                'operation' => 'error',
                "code" => 5,
                'message' => 'Zip file not exist',
                'cardUrl' => Yii::$app->urlManagerStaff->createAbsoluteUrl("/candidate-id-cards/")
            ];

        } else {// Download Zip File
            // Clear output buffer to avoid any additional data being sent
            if (ob_get_level()) {
                ob_end_clean();
            }

            return Yii::$app->response->sendFile($result['zip'], "IDCard.zip", [
               'mimeType' => 'application/zip',
                'inline' => false, // Force download
            ]);
        }
    }

    /**
     * List candidates having expired ID Cards
     */
    public function actionListExpired()
    {
        $candidate_name = Yii::$app->request->get("candidate_name");

        $query = Candidate::find()
            ->idExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted(); // only candidate with assigned work

        if($candidate_name) {
            $query->filterName($candidate_name);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Renew Candidate IDs
     */
    public function actionRenew()
    {
        if(empty(Yii::$app->params['inCodeception']))
            $transaction = Yii::$app->db->beginTransaction();

        $candidate_ids = Yii::$app->request->getBodyParam('candidates');

        foreach ($candidate_ids as $key => $value)
        {
            if(!$value)
                continue;

            $ID = CandidateIdCard::find()
                ->andWhere(['candidate_id' => $value])
                ->one();

            if(!$ID)
            {
                if(empty(Yii::$app->params['inCodeception']))
                    $transaction->rollBack();

                return [
                    'operation' => 'error',
                    'message' => 'Candidate ID not found'
                ];
            }

            $ID->expiry_date = date('Y-m-d', strtotime('+3 months'));
            
            if(!$ID->save()) {
                
                $transaction->rollBack();
                
                return [
                    'operation' => 'error',
                    'message' => $ID->errors
                ];
            }
        }

        if(empty(Yii::$app->params['inCodeception']))
            $transaction->commit();

        //log

        $candidates = Candidate::find()
            ->andWhere(['in', 'candidate_id', $candidate_ids])
            ->all();

        $names = ArrayHelper::map($candidates, 'candidate_id', 'candidate_name');

        Yii::info('[ID Cards Renewed] Candidate ID Cards for ['.implode(', ', $names).'] have been renewed by '.Yii::$app->user->identity->staff_name, __METHOD__);

        return [
            'operation' => 'success',
            'message' => 'Candidate ID Renewed Successfully'
        ];
    }

    /**
     * Return no. of expired ID Cards
     */
    public function actionTotalExpired()
    {
        $query = Candidate::find()
            ->idExpired()
            ->filterAssigned(); // only candidate with assigned work

        return [
            'total' => $query->count()
        ];
    }

    public function actionListWithoutCardWithJob() {

        return Candidate::find()
            ->idNeedGenerated()
            ->filterAssigned()
            ->all();
    }

    /**
     * @param $token
     * @param null $type
     * @return mixed|\yii\web\IdentityInterface|null
     */
    public function loginByAccessToken($token, $type = null)
    {
        $identity = Staff::findIdentityByAccessToken($token, $type);
        if ($identity && Yii::$app->user->login($identity)) {
            return $identity;
        } else {
            return null;
        }
    }

    /**
     * Finds the Candidate ID model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CandidateIdCard the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    private function findModel($id) 
    {
        if (($model = CandidateIdCard::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
