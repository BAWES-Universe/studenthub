<?php

namespace manager\modules\v1\controllers;

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
     * Return a List of Child Companies
     */
    public function actionListChild()
    {
        $company = Yii::$app->user->identity
            ->getCompany()->one();

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
        $company = Yii::$app->user->identity
            ->getCompany()->one();

        if($company->company_id == $id) {
            return Company::find()
                ->andWhere(['company_id' => $id])
                ->one();
        }

        $data = $company->getSubCompanies()
            ->filterCompany($id)
            ->one();

        if (!$data)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $data;
    }
}
