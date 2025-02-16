<?php

namespace candidate\modules\v1\controllers;

use kartik\mpdf\Pdf;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use candidate\models\CandidateWorkHistory;
use yii\web\NotFoundHttpException;


/**
 * Candidate controller
 */
class CandidateController extends Controller
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
     * get candidate work history
     * @return array|static[]
     */
    public function actionWorkHistory()
    {
        $model = CandidateWorkHistory::find()
            ->filterCandidate(\Yii::$app->user->id)
//            ->with('store')
//            ->asArray()
            ->orderBy('start_date DESC, id DESC')
            ->all();

        if(!$model)
            return [];

        return $model;
    }

    public function actionWorkHistoryDetail($id)
    {
        $model = CandidateWorkHistory::find()
            ->filterCandidate(\Yii::$app->user->id)
//            ->with('store')
//            ->asArray()
            ->andWhere(['id' => $id])
            ->one();

        if(!$model) {
            throw new NotFoundHttpException('The requested record does not exist.');
        }

        return $model;
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
    public function actionAppreciationCertificate($wid) {

        $candidate = Yii::$app->user->identity;

        $workHistory = $candidate
            ->getWorkHistory()
            ->andWhere(['id' => $wid])
            ->one();

        if (!$workHistory) {
            throw new NotFoundHttpException('The requested record does not exist.');
        }

        $this->layout = 'main';

        $content = $this->render('candidate-appreciation-certificate-pdf', [
            'candidate' => $candidate,
            'workHistory' => $workHistory
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
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionWorkingDates() {

        $candidate = Yii::$app->user->identity;
        $start_date = Yii::$app->request->get("start_date");
        $end_date = Yii::$app->request->get("end_date");

        $query = $candidate->getCandidateWorkingDates();

        if ($start_date && $end_date) {
            $query->filterByDateRange($start_date, $end_date);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }
}
