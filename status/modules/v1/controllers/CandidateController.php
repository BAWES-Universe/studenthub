<?php

namespace status\modules\v1\controllers;

use admin\models\CandidateWorkHistory;
use Yii;
use admin\models\Candidate;
use admin\models\Company;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;

class CandidateController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
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
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionSearch()
    {
        $query = Candidate::findCustom();

        $by = Yii::$app->request->get('by');

        if (Yii::$app->request->get('name', null)) {
            $query->filterName(Yii::$app->request->get('name'));
        }
        if (Yii::$app->request->get('email', null)) {
            $query->filterEmail(Yii::$app->request->get('email'));
        }
        if (Yii::$app->request->get('phone', null)) {
            $query->filterPhone(Yii::$app->request->get('phone'));
        }
        if (Yii::$app->request->get('civil', null)) {
            $query->filterCivil(Yii::$app->request->get('civil'));
        }
        if (Yii::$app->request->get('assigned', null)) {
            $query->totalAssigned();
        }

        if (Yii::$app->request->get('company_id', null)) {
            $company = Company::findOne(Yii::$app->request->get('company_id'));
            $query->filterCompany($company);
        }

        switch ($by) {
            case 'country_id' :
                $query->filterCountry(Yii::$app->request->get('country_id'));
                break;
            case 'university_id' :
                $query->filterUniversity(Yii::$app->request->get('university_id'));
                break;
            case 'review' :
                $query->byApprovalStatus(Yii::$app->request->get('review'));
                break;
            case 'store_id' :
                $query->filterStore(Yii::$app->request->get('store_id'));
                break;
            default:
                $query->byApprovalStatus(0);
                break;
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load candidate details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Return candidate's salary transfer with status
     */
    public function actionTransfers($id)
    {
        $model = $this->findModel((int) $id);

        return $model->getPaidTransferCandidate();
    }

    /**
     * get candidate work history
     * @param $id
     * @return array|\admin\modules\v1\controllers\CandidateController[]
     */
    public function actionWorkHistory($id)
    {
        $model = CandidateWorkHistory::find()
            ->filterCandidate($id)
            ->all();

        if(!$model)
            return [];

        return $model;
    }

    /**
     * Finds the Candidate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Candidate::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}