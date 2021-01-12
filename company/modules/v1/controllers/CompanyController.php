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
     * Return a List of Company Accounts available.
     */
    public function actionList()
    {
        $query = Yii::$app->user->identity->getManagedCompanies();
                
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

        $data = $company->getSubCompanies()
            ->filterCompany($id)->one();

        if (!$data)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $data;
    }
}
