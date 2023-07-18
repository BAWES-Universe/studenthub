<?php

namespace company\modules\v1\controllers;

use company\models\Contact;
use Yii;
use yii\data\ActiveDataProvider;
use company\models\Company;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;

/**
 * Company controller - Manage company accounts as Admin
 */
class CompanyController extends BaseController
{
    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['activate', 'options'];

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
            'collectionOptions' => ['GET', 'OPTIONS'],
            'resourceOptions' => ['GET', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * Return a List of Company available.
     */
    public function actionList()
    {
        return Yii::$app->user->identity
            ->getManagedCompanies()
            ->all();
    }

    /**
     * Return a List of Child Companies
     */
    public function actionListChild()
    {
        $company = Yii::$app->companyManager->getCompany();

        $query = Company::find()
            ->childCompany($company->company_id);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Return a company Detail
     */
    public function actionView($id)
    {
        $company = Yii::$app->companyManager->getCompany();

        if($company->company_id == $id) {
            return $company;
        }

        $data = $company->getSubCompanies()
            ->filterCompany($id)
            ->one();

        if (!$data)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $data;
    }

    /**
     * update company details
     */
    public function actionUpdate()
    {
        $model = Yii::$app->companyManager->getCompany();

        $model->setScenario('update');
        
        $model->company_name = ucfirst(Yii::$app->request->getBodyParam("name"));
        $model->company_common_name_en = ucfirst(Yii::$app->request->getBodyParam("common_name_en"));
        $model->company_common_name_ar = ucfirst(Yii::$app->request->getBodyParam("common_name_ar"));
        $model->company_description_en = Yii::$app->request->getBodyParam("description_en");
        $model->company_description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->company_website = Yii::$app->request->getBodyParam("website");
        $model->company_email = Yii::$app->request->getBodyParam("email");

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('company', "Company account updated successfully")
        ];
    }

    /**
     * Remove company logo
     */
    public function actionRemoveLogo()
    {
        $model = Yii::$app->companyManager->getCompany();

        if ($model->company_logo) {
            $model->deleteProfilePhotoFromCloudinary();
        }

        $model->company_logo = null;

        if (!$model->save(false)) {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }

        return [
            'operation' => 'success',
            'message' => Yii::t('company', 'Company Logo Removed Successfully')
        ];
    }

    /**
     * Allows user to change company logo
     */
    public function actionUpdateLogo() {

        $company_logo = urldecode(Yii::$app->request->getBodyParam('company_logo'));

        $model = Yii::$app->companyManager->getCompany();

        $model->company_logo = $company_logo;

        if(!$model->company_logo || $model->company_logo == "undefined") {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'invalid_input', [
                    'attribute' => 'company logo'
                ])
            ];
        }

        $result = $model->updateCompanyLogo();

        if ($result) {
            return [
                'operation' => 'success',
                'logo' => $model->company_logo,
                'message' => Yii::t('company', 'Company Logo Uploaded Successfully')
            ];
        } else {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }
    }

    /**
     * Allows user to change licence
     */
    public function actionUpdateLicence() {

        $commercial_licence = urldecode(Yii::$app->request->getBodyParam('commercial_licence'));

        $model = Yii::$app->companyManager->getCompany();

        $model->commercial_licence = $commercial_licence;

        if(!$model->commercial_licence || $model->company_logo == "commercial_licence") {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'invalid_input', [
                    'attribute' => 'commercial licence'
                ])
            ];
        }

        $result = $model->updateLicence();

        if ($result) {
            return [
                'operation' => 'success',
                'logo' => $model->company_logo,
                'message' => Yii::t('company', 'Company Logo Uploaded Successfully')
            ];
        } else {
            return [
                'operation' => 'error',
                'message' => $model->errors
            ];
        }
    }

    /**
     * activate account
     * @return array
     */
    public function actionActivate() {

        $commercial_licence = urldecode(Yii::$app->request->getBodyParam('commercial_licence'));
        $company_logo = urldecode(Yii::$app->request->getBodyParam('company_logo'));

        //$agent = Yii::$app->user->identity;

        $contact_auth_key = Yii::$app->request->getBodyParam('contact_auth_key');
        $contact_email = Yii::$app->request->getBodyParam('contact_email');
        $company_id = Yii::$app->request->getBodyParam('company_id');
        $password = Yii::$app->request->getBodyParam('password');

        $transaction = Yii::$app->db->beginTransaction();

        $contact = Contact::find()->andWhere([
            'contact_auth_key' => $contact_auth_key,
            "contact_email" => $contact_email
        ])->one();

        if (!$contact) {
            $transaction->rollBack();

            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        $contact->setPassword($password);

        if(!$contact->save()) {

            $transaction->rollBack();

            return [
                'operation' => 'error',
                'code' => 0,
                'message' => $contact->errors
            ];
        }

        //$model = Yii::$app->companyManager->getManagedCompany($company_id);

        $model = $contact->getCompanies()
            ->andWhere(['company_id' => $company_id])
            ->one();

        if (!$model) {
            $transaction->rollBack();

            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        $model->setScenario(\common\models\Company::SCENARIO_ACTIVATE);

        $model->company_logo = $company_logo;
        $model->commercial_licence = $commercial_licence;
        $model->company_website = Yii::$app->request->getBodyParam('website');
        $model->company_status_override = \common\models\Company::STATUS_ACTIVE;

        if(Yii::$app->language == "ar") {
            $model->company_description_ar = Yii::$app->request->getBodyParam('description');
        } else {
            $model->company_description_en = Yii::$app->request->getBodyParam('description');
        }

        if(!$model->commercial_licence || $model->company_logo == "commercial_licence")
        {
            $transaction->rollBack();

            return [
                'operation' => 'error',
                'code' => 1,
                'message' => Yii::t('company', 'invalid_input', [
                    'attribute' => 'commercial licence'
                ])
            ];
        }

        /*//upload logo

        if($model->company_logo && !$model->updateCompanyLogo())
        {
            $transaction->rollBack();

            return [
                'operation' => 'error',
                'code' => 2,
                'message' => $model->errors
            ];
        }

        //upload licence

        $result = $model->updateLicence();

        if (!$result)
        {
            $transaction->rollBack();

            return [
                'operation' => 'error',
                'code' => 3,
                'message' => $model->errors
            ];
        }*/

        if(!$model->save()) {
            $transaction->rollBack();

            return [
                'operation' => 'error',
                'code' => 4,
                'message' => $model->errors
            ];
        }

        $transaction->commit();

        return $this->_loginResponse($contact, $model);

        /*return [
            'operation' => 'success',
            'logo' => $model->company_logo,
            'message' => Yii::t('company', 'Company activated successfully'),
        ];*/
    }

    private function _loginResponse($contact, $company = false) {

        // Return Company access token if everything valid
        $accessToken = $contact->accessToken->token_value;

        if(!$company) {
            $company = $contact->getManagedCompanies()->one();
        }

        return [
            "operation" => "success",
            "token" => $accessToken,
            "company_id" => $company? $company->company_id: null,
            "profile_name" => $contact->contact_name,
            "email" => $contact->contact_email,
            "active_request_count" => $company? $company->getRequests()->activeRequest()->count() : 0
        ];
    }
}
