<?php

namespace staff\modules\v1\controllers;

use Yii;
use common\models\CandidateCertificate;
use common\models\CandidateWorkHistory;
use kartik\mpdf\Pdf;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CertificateController extends Controller
{
    public function behaviors() {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => \Yii::$app->params['allowedOrigins'],
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
    public function actions() {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    public function actionList() {

        $candidate_id = Yii::$app->request->get('candidate_id');
        $exam_uuid = Yii::$app->request->get('exam_uuid');
        $type = Yii::$app->request->get('type');
        $store_id = Yii::$app->request->get('store_id');
        $company_id = Yii::$app->request->get('company_id');
        $page = Yii::$app->request->get('page');

        $query = CandidateCertificate::find();

        if ($store_id) {
            $query->andWhere(['store_id' => $store_id]);
        }

        if ($candidate_id) {
            $query->andWhere(['candidate_id' => $candidate_id]);
        }

        if ($exam_uuid) {
            $query->andWhere(['exam_uuid' => $exam_uuid]);
        }

        if ($type) {
            $query->andWhere(['certificate_type' => $type]);
        }

        if ($company_id) {
            $query->andWhere(['company_id' => $company_id]);
        }

        if ($page == -1) {
            return new ActiveDataProvider([
                'query' => $query,
                "pagination" => false
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * add certificate manually
     * @return array
     */
    public function actionCreate()
    {
        $model = new CandidateCertificate();
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->candidate_work_history_id = Yii::$app->request->getBodyParam("candidate_work_history_id");;
        $model->certificate_type = CandidateCertificate::TYPE_EXPERIENCE;
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent_company_id");
        $model->start_date =Yii::$app->request->getBodyParam("start_date");
        $model->end_date = Yii::$app->request->getBodyParam("end_date");
        $model->staff_id = Yii::$app->user->getId();

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Certificate created successfully"),
        ];
    }

    /**
     * @param $id
     * @return mixed|string[]
     * @throws NotFoundHttpException
     * @throws \Mpdf\MpdfException
     * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws \setasign\Fpdi\PdfParser\PdfParserException
     * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCertificate($id) {

        $certificate = $this->findModel($id);

        $this->layout = 'main';

        $content = $this->render('candidate-certificate-pdf', [
            'candidate' => $certificate->candidate,
            'certificate' => $certificate
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            'marginTop' => 5,
            'marginRight' => 6,
            'marginLeft' => 6,
            // portrait orientation
            'orientation' => Pdf::ORIENT_LANDSCAPE,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => [
                '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                'css/pdf.css'
            ],
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }

    /**
     * generate certificate from work history
     * @param $id
     * @return array
     */
    public function actionFromWorkHistory($id)
    {
        $workHistoryModel = CandidateWorkHistory::findOne($id);

        if(!$workHistoryModel) {
            return [
                "operation" => "error",
                "message" => "Work history not found!"
            ];
        }

        $start_date = Yii::$app->request->getBodyParam("start_date");

        if (empty($start_date)) {
            $start_date = $workHistoryModel->start_date;
        }

        $end_date = Yii::$app->request->getBodyParam("end_date");

        if (empty($end_date)) {
            $end_date = !empty($workHistoryModel->end_date) ?
                $workHistoryModel->end_date: new \yii\db\Expression('NOW()');
        }

        $model = new CandidateCertificate();
        $model->candidate_id = $workHistoryModel->candidate_id;
        $model->candidate_work_history_id = $id;
        $model->certificate_type = CandidateCertificate::TYPE_EXPERIENCE;
        $model->store_id = $workHistoryModel->store_id;
        $model->company_id = $workHistoryModel->company_id;
        $model->parent_company_id = $workHistoryModel->parent_company_id;
        $model->start_date = $start_date;
        $model->end_date = $end_date;
        $model->staff_id = Yii::$app->user->getId();

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Certificate created successfully"),
        ];
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->candidate_work_history_id = Yii::$app->request->getBodyParam("candidate_work_history_id");;
        $model->certificate_type = CandidateCertificate::TYPE_EXPERIENCE;
        $model->store_id = Yii::$app->request->getBodyParam("store_id");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent_company_id");
        $model->start_date =Yii::$app->request->getBodyParam("start_date");
        $model->end_date = Yii::$app->request->getBodyParam("end_date");
        $model->staff_id = Yii::$app->user->getId();

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Certificate updated successfully"),
        ];
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (!$model->delete()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('app', "Certificate deleted successfully"),
        ];
    }

    /**
     * Finds the Candidate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CandidateCertificate::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}