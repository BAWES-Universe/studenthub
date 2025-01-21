<?php

namespace admin\modules\v1\controllers;

use admin\models\Company;
use admin\models\Staff;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\Candidate;
use admin\models\CandidateWorkHistory;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Candidate controller - Manage Candidate accounts as Admin
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
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionSearch()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $by = Yii::$app->request->get('by');
        $name = Yii::$app->request->get('name', null);
        $match_request_id = Yii::$app->request->get('match_request_id');

        $query = Candidate::findCustom();

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        if($match_request_id) {
            $query->filterByRequestRequirement($match_request_id);
        }

        if ($name && !is_numeric($name)) {
            $query->filterName($name);
        }
        if ($name && is_numeric($name)) {
            $query->filterById($name);
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
        if ($dateFilterBy = Yii::$app->request->get('type', null)) {
            $query->dateFilterBy($dateFilterBy,Yii::$app->request->get('start_date', null),Yii::$app->request->get('end_date', null));
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
//                $query->byApprovalStatus(0);
//                $query->orderById();
                break;
        }
//        return $query->getSqlQuery();
        return new ActiveDataProvider([
            'query' => $query
        ]);
        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Return a List of Candidate Accounts by
     * search criteria
     */
    public function actionReportSearch()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $query = CandidateWorkHistory::find();

        if($currency) {
            $query->joinWith(['candidate'])
                ->andWhere(['candidate.currency_code' => $currency]);
        }

        if (Yii::$app->request->get('start', null) || Yii::$app->request->get('end', null)) {
            $query->filterByJoiningDate(
                Yii::$app->request->get('start',null),
                Yii::$app->request->get('end',null),
                Yii::$app->request->get('company_id',null)
            );
        }
        if (Yii::$app->request->get('currently_working')) { // reason in case if candidate were hired but not working now that we need to check
            $query->notDeleted();
            $query->totalAssigned();
            if (Yii::$app->request->get('company_id')) {
                $company = Company::findOne(Yii::$app->request->get('company_id'));
                $query->filterCompanyByCandidate($company);
                $query->filterByJoiningDate(
                    Yii::$app->request->get('start', null),
                    Yii::$app->request->get('end', null),
                    Yii::$app->request->get('company_id', null)
                );
            }
        } else if(Yii::$app->request->get('company_id',null)) {
            $query->filterCompany(Yii::$app->request->get('company_id'));
            $query->notDeleted();
            $query->totalAssigned();
        } else {
            $query->notDeleted();
            $query->totalAssigned();
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a No of Candidate to review
     * Return a No of Payable candidate also
     */
    public function actionTotalToReview()
    {
        $currency = Yii::$app->request->headers->get("Currency", "KWD");

        $query = Candidate::find()
            ->byApprovalStatus(0);

        if($currency) {
            $query->andWhere(['candidate.currency_code' => $currency]);
        }

        $payable = Candidate::getTotalPayableCandidate($currency);

        return [
            'total' => $query->count(),
            'payable' => $payable['payable']
        ];
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionLogin($id)
    {
        $model = $this->findModel((int)$id);

        $model->generateAuthKey();

        if(!$model->save(false)) {
            return [
                "operation" => "error",
                'message' => $model->errors,
                'redirect' => Yii::$app->params['candidateAppUrl']
            ];
        }

        $url = Yii::$app->params['candidateAppUrl']. '?auth_key='.$model->candidate_auth_key;

        return [
            'redirect' => $url
        ];
    }

    /**
     * Approve candidate account
     * @param $id
     * @return array
     */
    public function actionApprove($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        $model->approved = 1;

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('['.$model->candidate_email.' Account Approved] Candidate account approved by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account approved successfully"
        ];
    }

    /**
     * Approve candidate account
     * @param $id
     * @return array
     */
    public function actionRestore($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found"
            ];
        }

        $model->scenario = 'deleteCandidate';
        $model->deleted = 0;

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }

        Yii::info('['.$model->candidate_email.' Account Approved] Candidate account restored by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account restored successfully"
        ];
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
     * load candidate details
     * @param type $id
     * @return type
     */
    public function actionView($id)
    {
        ini_set('memory_limit','-1');
        return $this->findModel($id);
    }
    
    /**
     * get candidate work history
     * @param $id
     * @return array|static[]
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
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        if (Yii::$app->user->identity->admin_limited_access) {
            return [
                "operation" => "error",
                "message" => "You are not allowed to perform this action"
            ];
        }

        $model = Candidate::findOne(['candidate_id'=>$id]);

        if (!$model || ($model && $model->deleted)) {
            return [
                "operation" => "success",
                "message" => "Candidate account already deleted"
            ];
        }

        $model->scenario = 'deleteCandidate';
        $model->deleted = 1;

        if (!$model->save()) {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance."
                ];
            }
        }
        Yii::$app->algolia->delete(Yii::$app->params['algolia_candidate_index'], $model->candidate_id);

        Yii::info('['.$model->candidate_email.' Account Deleted] Candidate account Deleted by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Candidate account deleted successfully"
        ];
    }

    /**
     * Reset staff password
     * @param $id
     * @return array
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel((int) $id);
        $password = Yii::$app->request->getBodyParam("password", null);
        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Candidate not found",
                "code" => 1
            ];
        }

        if (!$password) {
            $password = Yii::$app->security->generateRandomString(5);
        }

        $model->password = $password;
        $model->save(false);

        //Send Email to user
        Candidate::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
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
