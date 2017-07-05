<?php
namespace admin\models;

use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "Company".
 * It extends from \common\models\Company but with custom functionality for this application module
 */
class Company extends \common\models\Company {

    /**
     * @inheritdoc
     */
    public function fields()
    {                
        // Whitelisted fields to return
        return [
            'company_id',
            'parent_company_id',
            'company_name',
            'company_email',
            'company_status',
            'total_candidates' => function($model) {
                        
                // create company_id array from all sub companies and self 

                $companies = Company::findAll(['parent_company_id' => $model->company_id]);

                $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

                $company_ids[] = $model->company_id;

                // create store_id array 

                $stores = Store::find()
                    ->where(['in', 'company_id', $company_ids])
                    ->all();

                $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

                return Candidate::find()
                    ->where(['in', 'store_id', $store_ids])
                    ->count();
            },
            'subcompanies' => function($model) {
                return $model->subCompanies;
            },
            'stores' => function($model) {
                return $model->stores;
            }
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies($modelClass = "\admin\models\Company")
    {
        return parent::getSubCompanies($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\admin\models\Store")
    {
        return parent::getStores($modelClass);
    }
}
