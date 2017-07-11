<?php
namespace admin\models;

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
                return self::getTotalCandidateCount($model->company_id);
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
