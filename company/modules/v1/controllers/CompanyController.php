<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use company\models\Company;

/**
 * Company controller - Manage company accounts as Admin
 */
class CompanyController extends BaseController
{
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
            "message" => Yii::t('company', "Company update successfully")
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
}
