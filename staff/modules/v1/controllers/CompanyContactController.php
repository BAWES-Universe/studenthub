<?php

namespace staff\modules\v1\controllers;

use staff\models\ContactEmail;
use staff\models\ContactPhone;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use staff\models\CompanyContact;
use staff\models\Contact;
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
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionLogin($id)
    {
        $model = $this->findModel($id);

        $model->generateAuthKey(null);

        if(!$model->save(false)) {
            return [
                "operation" => "error",
                'message' => $model->errors,
                'redirect' => Yii::$app->params['companyAppUrl']
            ];
        }

        $url = Yii::$app->params['companyAppUrl']. '?auth_key='.$model->contact_auth_key;

        return [
            'redirect' => $url
        ];
    }

    /**
     * Return a List of CompanyContact Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $company_id = Yii::$app->request->get('company_id');
        $filter_email_unverified = Yii::$app->request->get('filter_email_unverified');
        $q = Yii::$app->request->get('query');

        $query = Contact::find();

        if($q) {
            $query->joinWith(['contactEmails', 'contactPhones'])
                ->andWhere([
                    'OR',
                    ['like', 'contact_name', $q],
                    ['like', 'contact_email.email_address', $q],
                    ['like', 'contact_phone.phone_number', $q]
                ]);
        }

        if($company_id) {
            // we need to show position in contact listing page
            // each contact have has many contact so using join to
            // return as array. and to ignore password.
            $query
                ->addSelect('contact.contact_name,contact.contact_uuid,contact.contact_email,contact.contact_email_verification')
                ->addSelect('contact.contact_receive_email,contact.contact_receive_email,contact.contact_updated_at')
                ->addSelect('contact.contact_receive_notification,contact.contact_created_at')
                ->joinWith(['contactEmails', 'contactPhones','companyContact'])
                ->orderBy('created_at ASC')
                ->andWhere(['company_contact.company_id' => $company_id])
                ->asArray();
        }

        if($filter_email_unverified) {
            $query->andWhere(['contact_email_verification' => \common\models\Contact::EMAIL_NOT_VERIFIED]);
        }

        return new ActiveDataProvider([
            'query' => $query,
          //  'pagination' => false
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
     * retrun access details
     * @return \staff\models\CompanyContact|null
     */
    public function actionViewCompanyContact() {
        $company_id = Yii::$app->request->get('company_id');
        $contact_uuid = Yii::$app->request->get('contact_uuid');

        return CompanyContact::find()
            ->andWhere(['company_id' => $company_id, 'contact_uuid' => $contact_uuid])
            ->one();
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
        $model->contact_receive_email = Yii::$app->request->getBodyParam("receive_email");
        $model->contact_receive_notification = Yii::$app->request->getBodyParam("receive_notification");

        $model->setPassword(Yii::$app->request->getBodyParam("password"));

        $model->generateAuthKey();

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

        if (!$model->contact_email_verification) {
            $model->sendVerificationEmail();
        }

        foreach($emails as $email) {

            if(!$email['email_address'])
                continue;

            $em = new ContactEmail();
            $em->contact_uuid = $model->contact_uuid;
            $em->email_address = $email['email_address'];
            $em->save();
        }

        foreach($phones as $phone) {

            if(!$phone['phone_number'])
                continue;
            
            $em = new ContactPhone();
            $em->contact_uuid = $model->contact_uuid;
            $em->phone_number = $phone['phone_number'];
            $em->save();
        }

        //add to team

        $company_id = Yii::$app->request->getBodyParam("company_id");

        if($company_id) {

            $companyContact = new CompanyContact();
            $companyContact->contact_uuid = $model->contact_uuid;
            $companyContact->company_id = $company_id;
            $companyContact->allow_access = Yii::$app->request->getBodyParam("allow_access");
            $companyContact->contact_position = Yii::$app->request->getBodyParam("contact_position");

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
     * check if email available for contact
     * @return array
     */
    public function actionIsEmailExists() {
        $email = Yii::$app->request->get('email');

        if ($email) {
            $model = Contact::find()
                ->andWhere(new \yii\db\Expression('contact_email LIKE :term', [':term' => $email.'%']))
                ->one();

            return [
                'contact' => $model
            ];
        }
        return [];
    }

    /**
     * add contact to team
     * @return array|string[]
     */
    public function actionAddToTeam() {

        $company_id = Yii::$app->request->getBodyParam("company_id");
        $contact_uuid = Yii::$app->request->getBodyParam ("contact_uuid");
        $allow_access = Yii::$app->request->getBodyParam ("allow_access");
        $contact_position = Yii::$app->request->getBodyParam("contact_position");

        $companyContact = new CompanyContact();
        $companyContact->contact_uuid = $contact_uuid;
        $companyContact->company_id = $company_id;
        $companyContact->allow_access = $allow_access;
        $companyContact->contact_position = $contact_position;

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
     * Create a brand account
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        // Attempt to create new account
        $model = Contact::findOne($id);

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Company Contact not found."
            ];
        }

        $model->contact_name = Yii::$app->request->getBodyParam("name");
        $model->contact_email = Yii::$app->request->getBodyParam("email");
        $model->contact_receive_email = Yii::$app->request->getBodyParam("receive_email");
        $model->contact_receive_suggestions = Yii::$app->request->getBodyParam("receive_suggestions");
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

        if (Yii::$app->request->getBodyParam("company_id")) {
            CompanyContact::updateAll(
                [
                    'contact_position' => Yii::$app->request->getBodyParam("contact_position"),
                    'allow_access' => Yii::$app->request->getBodyParam("allow_access")
                ],
                [
                    'contact_uuid' => $model->contact_uuid,
                    'company_id' => Yii::$app->request->getBodyParam("company_id")
                ]
            );
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
     * @param $id
     * @return array|string[]
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionRemoveFromTeam($id)
    {
        $company_id = Yii::$app->request->getBodyParam("company_id");

        $model = CompanyContact::find()
            ->andWhere(['company_id' => $company_id, "contact_uuid" => $id])
            ->one();

        if(!$model) {
            return [
                "operation" => "error",
                "message" => "Company Contact not found or already deleted"
            ];
        }

        if(!$model->delete()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Company Contact removed successfully from company team"
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

        /*$notes = $model->getNotes()->count();
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
        }*/

        $model->deleted = true;

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "Company Contact marked deleted successfully"
        ];
    }

    /**
     * send verification email to contact's email address
     * @return string[]
     */
    public function actionResendVerificationEmail() {

        $id = Yii::$app->request->get('contact_uuid');

        $model = Contact::findOne($id);

        if(!$model) {
            return [
                "operation" => "Error",
                "message" => "Invalid Contact"
            ];
        }

        if (!$model->contact_email_verification) {
            $model->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => "Verification email sent successfully"
            ];

        } else  {
            return [
                "operation" => "Error",
                "message" => "Email already Validated"
            ];
        }
    }

    /**
     * mark email verified manually
     * @return string[]
     */
    public function actionMarkEmailVerified() {

        $id = Yii::$app->request->getBodyParam('contact_uuid');

        $model = Contact::findOne($id);

        if(!$model) {
            return [
                "operation" => "Error",
                "message" => "Invalid Contact"
            ];
        }

        if (!$model->contact_email_verification) {

            $model->contact_email_verification = true;
            $model->contact_email_verified_by = Yii::$app->user->getId();

            if(!$model->save()) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }

            return [
                "operation" => "success",
                "message" => "Email verified successfully"
            ];

        } else  {
            return [
                "operation" => "error",
                "message" => "Email already Validated"
            ];
        }
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
        if (($model = Contact::findOne(['contact_uuid'=>$id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
