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
                return (int)self::getTotalCandidateCount($model->company_id);
            },
            'total_subcompanies' => function($model) {
                return (int)$model->getSubCompanies()->count();
            },
            'total_stores' => function($model) {
                return (int)$model->getStores()->count();
            }
        ];
    }

     /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidates',
            'subCompanies',
            'stores'
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
