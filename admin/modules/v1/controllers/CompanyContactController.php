<?php

namespace admin\modules\v1\controllers;

use admin\models\Company;
use staff\models\Contact;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use common\models\CompanyContact;
use common\models\ContactPhone;
use common\models\ContactEmail;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Contact controller - Manage CompanyContact as Admin
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

        $query = CompanyContact::find()
            ->orderBy('created_at ASC');
        if($company_id) {
            $query->filterWhere(['company_id' => $company_id]);
        }
        
        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load company contact details
     * @param type $id
     * @return type
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
        $model = new Contact();

        $model->contact_name = Yii::$app->request->getBodyParam("name");
        $model->contact_email = Yii::$app->request->getBodyParam("email");
        $model->contact_position = Yii::$app->request->getBodyParam("position");
        $model->contact_receive_email = Yii::$app->request->getBodyParam("receive_email");
        $model->contact_receive_notification = Yii::$app->request->getBodyParam("receive_notification");

        $model->setPassword(Yii::$app->request->getBodyParam("password"));

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

            $em = new ContactEmail;
            $em->contact_uuid = $model->contact_uuid;
            $em->email_address = $email['email_address'];
            $em->save();
        }

        foreach($phones as $phone) {

            if(!$phone['phone_number'])
                continue;
            
            $em = new ContactPhone;
            $em->contact_uuid = $model->contact_uuid;
            $em->phone_number = $phone['phone_number'];
            $em->save();
        }

        //add to team

        $company_id = Yii::$app->request->getBodyParam("company_id");

        if($company_id) {

            $companyContact = new \staff\models\CompanyContact();
            $companyContact->contact_uuid = $model->contact_uuid;
            $companyContact->company_id = $company_id;
            $companyContact->role = Yii::$app->request->getBodyParam("role");

            if (!$companyContact->save()) {
                return [
                    "operation" => "error",
                    "message" => $companyContact->errors
                ];
            }
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
        $model->contact_email = Yii::$app->request->getBodyParam("email");
        $model->contact_position = Yii::$app->request->getBodyParam("position");
        $model->contact_receive_email = Yii::$app->request->getBodyParam("receive_email");
        $model->contact_receive_notification = Yii::$app->request->getBodyParam("receive_notification");

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

        ContactEmail::deleteAll(['contact_uuid' => $model->contact_uuid]);
        ContactPhone::deleteAll(['contact_uuid' => $model->contact_uuid]);

        foreach($emails as $email) {

            if(!$email['email_address'])
                continue;

            $em = new ContactEmail;
            $em->contact_uuid = $model->contact_uuid;
            $em->email_address = $email['email_address'];
            $em->save();
        }

        foreach($phones as $phone) {

            if(!$phone['phone_number'])
                continue;
            
            $em = new ContactPhone;
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
     * check if email available for contact
     * @return array
     */
    public function actionIsEmailExists() {
        $email = Yii::$app->request->get('email');

        $model = Contact::find()
            ->filterWhere(['contact_email' => $email])
            ->one();

        return [
            'contact' => $model
        ];
    }

    /**
     * add contact to team
     * @return array|string[]
     */
    public function actionAddToTeam() {

        $company_id = Yii::$app->request->getBodyParam("company_id");
        $role = Yii::$app->request->getBodyParam ("role");
        $contact_uuid = Yii::$app->request->getBodyParam ("contact_uuid");

        $companyContact = new \staff\models\CompanyContact();
        $companyContact->contact_uuid = $contact_uuid;
        $companyContact->company_id = $company_id;
        $companyContact->role = $role;

        if (!$companyContact->save()) {
            return [
                "operation" => "error",
                "message" => $companyContact->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Contact added to team successfully"
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

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Company Contact not found or already deleted"
            ];
        }

        ContactEmail::deleteAll(['contact_uuid' => $model->contact_uuid]);
        ContactPhone::deleteAll(['contact_uuid' => $model->contact_uuid]);
        
        $model->delete();

        return [
            "operation" => "success",
            "message" => "Company Contact deleted successfully"
        ];
    }

    /**
     * Reset Company password
     * @param $id
     * @return array
     */
    public function actionResetPassword($id)
    {
        $model = $this->findModel((int) $id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Company not found",
                "code" => 1
            ];
        }

        $password = Yii::$app->security->generateRandomString(5);

        $model->setPassword($password);
        $model->save(false);

        //Send Email to user
        Contact::passwordMail($model, $password);

        return [
            "operation" => "success",
            "message" => "New password sent to registered email successfully"
        ];
    }

    /**
     * Finds the CompanyContact model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Transfer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Contact::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
