<?php
namespace admin\models;

use Yii;
use yii\helpers\ArrayHelper;
use admin\models\Store;
use admin\models\Candidate;

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
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies()
    {
        return $this->hasMany(Company::className(), ['parent_company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores()
    {
        return $this->hasMany(Store::className(), ['company_id' => 'company_id']);
    }
}
