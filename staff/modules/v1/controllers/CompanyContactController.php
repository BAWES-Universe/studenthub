<?php

namespace staff\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\CompanyContact;
use common\models\CompanyContactPhone;
use common\models\CompanyContactEmail;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;

/**
 * CompanyContact controller - Manage CompanyContact as Staff
 */
class CompanyContactController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
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
            'class' => HttpBearerAuth::className(),
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
     * Return a List of CompanyContact Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $company_id = Yii::$app->request->get('company_id');
        $q = Yii::$app->request->get('query');
        
        $query = CompanyContact::find()
            ->orderBy('contact_created_datetime ASC');

        if($q) {
            $query->joinWith(['companyContactEmails', 'companyContactPhones'])
                ->andWhere([
                    'OR',
                    ['like', 'contact_name', $q],
                    ['like', 'company_contact_email.email_address', $q],
                    ['like', 'company_contact_phone.phone_number', $q]
                ]);
        }

        if($company_id) {
            $query->filterWhere(['company_id' => $company_id]);
        }
        
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load company contact details
     * @param $id
     * @return CompanyContact
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }
    
    /**
     * Create a brand account
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new 
        $model = new CompanyContact();

        $model->contact_name = Yii::$app->request->getBodyParam("name");
        $model->contact_position = Yii::$app->request->getBodyParam("position");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        
        $emails = Yii::$app->request->getBodyParam("emails");
        $phones = Yii::$app->request->getBodyParam("phones");

        if (!$model->save())
        {
            if(isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the contact details, please contact us for assistance."
                ];
            }
        }

        foreach($emails as $email) {

            if(!$email['email_address'])
                continue;

            $em = new CompanyContactEmail; 
            $em->contact_uuid = $model->contact_uuid;
            $em->email_address = $email['email_address'];
            $em->save();
        }

        foreach($phones as $phone) {

            if(!$phone['phone_number'])
                continue;
            
            $em = new CompanyContactPhone; 
            $em->contact_uuid = $model->contact_uuid;
            $em->phone_number = $phone['phone_number'];
            $em->save();
        }

        return [
            "operation" => "success",
            "message" => "Contact details added successfully"
        ];
    }

    /**
     * Create a brand account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = $this->findModel($id);

        if(!$model){
            return [
                    "operation" => "error",
                    "message" => "Company Contact not found."
                ];
        }

        $model->contact_name = Yii::$app->request->getBodyParam("name");
        $model->contact_position = Yii::$app->request->getBodyParam("position");
        $model->company_id = Yii::$app->request->getBodyParam("company_id");
        
        $emails = Yii::$app->request->getBodyParam("emails");
        $phones = Yii::$app->request->getBodyParam("phones");

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
                    "message" => "We've faced a problem updating the contact details, please contact us for assistance."
                ];
            }
        }

        CompanyContactEmail::deleteAll(['contact_uuid' => $model->contact_uuid]);
        CompanyContactPhone::deleteAll(['contact_uuid' => $model->contact_uuid]);

        foreach($emails as $email) {

            if(!$email['email_address'])
                continue;

            $em = new CompanyContactEmail; 
            $em->contact_uuid = $model->contact_uuid;
            $em->email_address = $email['email_address'];
            $em->save();
        }

        foreach($phones as $phone) {

            if(!$phone['phone_number'])
                continue;
            
            $em = new CompanyContactPhone; 
            $em->contact_uuid = $model->contact_uuid;
            $em->phone_number = $phone['phone_number'];
            $em->save();
        }

        return [
            "operation" => "success",
            "message" => "Contact details successfully updated"
        ];
    }

    /**
     * Delete an account
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $notes = $model->getNotes()->count();
        $requests = $model->getRequests()->count();

        if ($notes || $requests) {
            return [
                "operation" => "error",
                "message" => "Company Contact can't be deleted. Its in use"
            ];
        }

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Company Contact not found or already deleted"
            ];
        }

        CompanyContactEmail::deleteAll(['contact_uuid' => $model->contact_uuid]);
        CompanyContactPhone::deleteAll(['contact_uuid' => $model->contact_uuid]);
        
        $model->delete();

        return [
            "operation" => "success",
            "message" => "Company Contact deleted successfully"
        ];
    }
    
    /**
     * Finds the CompanyContact model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CompanyContact the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CompanyContact::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
