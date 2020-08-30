<?php
namespace admin\models;

/**
 * This is the model class for table "Company".
 * It extends from \common\models\Company but with custom functionality for this application module
 */
class Company extends \common\models\Company {

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {
        $scenarios = parent::scenarios();

        $scenarios['adminUpdate'] = ['company_name', 'company_email', 'parent_company_id', 'company_hourly_rate', 'company_bonus_commission',
            'company_common_name_en', 'company_common_name_ar', 'company_description_en', 'company_description_ar', 'company_website',
            'company_logo'];
        
        return $scenarios;
    }
        
    /**
     * @inheritdoc
     */
    public function fields()
    {                
        // Whitelisted fields to return
        $fields = parent::fields();

        return array_merge($fields, [
            'company_bonus_commission' => function($model) {
                if($model->company_bonus_commission)
                    return (double)$model->company_bonus_commission;
                
                if($model->parentCompany)
                    return (double)$model->parentCompany->company_bonus_commission;
            },
            'company_hourly_rate' => function($model) {
                if($model->company_hourly_rate)
                    return (double)$model->company_hourly_rate;
                
                if($model->parentCompany)
                    return (double)$model->parentCompany->company_hourly_rate;
            },
            'total_candidates' => function($model) {
                return (int)self::getTotalCandidateCount($model->company_id);
            },
            'total_subcompanies' => function($model) {
                return (int)$model->getSubCompanies()->count();
            },
            'total_stores' => function($model) {
                return (int)$model->getStores()->count();
            }
        ]);
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
    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\admin\models\Note")
    {
        return parent::getStores($modelClass);
    }
}
