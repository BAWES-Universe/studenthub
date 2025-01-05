<?php

namespace staff\modules\v1\controllers;

use common\models\CandidateEvalDeptQues;
use common\models\CandidateEvaluation;
use common\models\CandidateEvaluationAnswer;
use common\models\Item;
use kartik\mpdf\Pdf;
use staff\models\Invoice;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;
use common\models\CandidateEvalQues;

/**
 * Candidate controller - Manage Candidate accounts as Admin
 */
class CandidateEvaluationController extends Controller
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
     * list question by dept id
     * @param $id
     * @return CandidateEvalDeptQues[]
     */
    public function actionListQuestionByDept($id)
    {
        return CandidateEvalDeptQues::findAll(['dept_id'=>$id]);
    }

    /**
     * @return array|string[]
     * @throws \yii\db\Exception
     */
    public function actionCreate() {
        $transaction = Yii::$app->db->beginTransaction();
        $model = new CandidateEvaluation();
        $model->candidate_id = Yii::$app->request->post('candidateID');
        $model->dept_id = Yii::$app->request->post('dept');
        $model->start_date = Yii::$app->request->post('start_date');
        $model->end_date = Yii::$app->request->post('end_date');
        if (!$model->save()) {
            $transaction->rollBack();
            return [
                'operation' => 'error',
                'message' =>$model->getErrors()
            ];
        }

        $last = CandidateEvaluation::find()->orderBy('created_at DESC')->asArray()->one(); // last record not fetching

        foreach (Yii::$app->request->post('questionAnswer') as $answers) {

            $modelAnswer = new CandidateEvaluationAnswer();
            $modelAnswer->can_eval_uuid = $last['can_eval_uuid'];
            $modelAnswer->ceq_uuid = isset($answers['ceq_uuid'])? $answers['ceq_uuid']: null;
            $modelAnswer->answer = isset($answers['answer'])? $answers['answer']: null;
            $modelAnswer->question = isset($answers['question'])? $answers['question']: null;
            $modelAnswer->rating = (isset($answers['rating']))?$answers['rating']:1;

            if (!$modelAnswer->save()) {

                $transaction->rollBack();
                return [
                    'operation' => 'error',
                    'message' =>$modelAnswer->getErrors()
                ];
            }
        }

        $transaction->commit();

        return [
            'operation' => 'success',
            'message' => 'Report saved successfully'
        ];
    }

    /**
     * @param $id
     * @return ActiveDataProvider
     */
    public function actionListReport($id)
    {
        $query = CandidateEvaluation::find();
        $query->andWhere(['candidate_id'=>$id]);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @param $id
     * @return CandidateEvaluation|null
     */
    public function actionViewReport($id)
    {
        return CandidateEvaluation::findOne($id);
    }

    /**
     * Download Transfer as PDF
     * @param $id
     * @return array|mixed
     */
    public function actionPdf($id)
    {
        $report = CandidateEvaluation::find()
            ->joinWith(['candidate','staff','questionAnswer'])
            ->andWhere(['candidate_evaluation.can_eval_uuid'=>$id])
            ->one();

        $this->layout = 'pdf';

        $content = $this->render('@staff/modules/v1/views/candidate/candidate-evaluation-report-pdf.php', [
            'report' => $report,
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_BROWSER,
            // your html content input
            'content' => $content,
            // any css to be embedded if required
            'cssInline' => 'body {line-height: 1.85714286em;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;font-family: \'Open Sans\', \'Helvetica\', \'Arial\', sans-serif;color: #666666;} h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {font-family: \'Open Sans\', \'Helvetica\', \'Arial\', sans-serif;color: #252525;font-variant-ligatures: common-ligatures;margin-top: 0;margin-bottom: 0;}',
            // set mPDF properties on the fly
            'options' => [],//['title' => 'Booking #'.$id],
            // call mPDF methods on the fly
        ]);

        header('Access-Control-Allow-Origin: *');
        return $pdf->render();
    }
    /**
     * Finds the Candidate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CandidateEvalQues the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CandidateEvalQues::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
