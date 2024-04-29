<?php

namespace company\modules\v1\controllers;

use common\models\StoreAssignmentRequest;
use Yii;
use yii\data\ActiveDataProvider;
use company\models\Store;
use company\models\Company;

/**
 * Store controller - Manage store as Admin
 */
class StoreController extends BaseController
{
    /**
     * @return array|string[]
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionStoreAssignmentRequest() {

        $store_id = Yii::$app->request->getBodyParam("store_id");

        //validate store

        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $store = Store::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->filterByStoreId($store_id)
            ->one();

        if (!$store)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        $model = new StoreAssignmentRequest();
        $model->candidate_id = Yii::$app->request->getBodyParam("candidate_id");
        $model->store_id = $store_id;

        if(!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }

        return [
            "operation" => "success",
            "message" => "We received your request"
        ];
    }

    /**
     * @param $id
     * @return array|\common\models\query\Store
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionView($id)
    {
        $companyIds = Yii::$app->companyManager->getCompanyIds();

        $store = Store::find()
            ->andWhere(['in', 'company_id', $companyIds])//current company and childs
            ->filterByStoreId($id)    
            ->one();

        if (!$store)
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');

        return $store;
    }
    
    /**
     * Return a List of Store by companyId if provided
     * else by current login company id.
     * @param null $companyId
     * @return array|ActiveDataProvider
     */
    public function actionList($companyId = null)
    {
        $company = Yii::$app->companyManager->getCompany();

        //validate company id belong to sub company of current company 
        if ($companyId) {

            $sub_company = Company::findOne([
                'parent_company_id' => $company->company_id,
                'company_id' => $companyId
            ]);

            if(empty($sub_company)) 
                return [
                    "operation" => "error",
                    "message" => 'Company not found'
                ];

        } else {
            //show store for current login company by default 
            $companyId = $company->company_id;    
        }       

        $query = Store::find()
            ->filterCompany($companyId);

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * @return array
     */
    public function actionIndex()
    {
        $company = Yii::$app->companyManager->getCompany();
        
        if (isset($company->subCompanies) && count($company->subCompanies)>0) {

            $query = $company
                ->getSubCompanyStores();
//                ->getSubCompanies();
            return new ActiveDataProvider([
                'query' => $query
            ]);
            
        }
        
        if (isset($company->stores) && count($company->stores)>0) {

            $query = $company
                ->getStores();
            
            return new ActiveDataProvider([
                'query' => $query
            ]);
        }
    }
}
