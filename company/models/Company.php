<?php
namespace company\models;
/**
 * This is the model class for table "Company".
 * It extends from \common\models\Company but with custom functionality for this application module
 */
class Company extends \common\models\Company {

    /**
     * @return array
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
            'company_hourly_rate' => function($model) {
                if($model->company_hourly_rate)
                    return (double)$model->company_hourly_rate;
                
                if($model->parentCompany)
                    return (double)$model->parentCompany->company_hourly_rate;
            },                     
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
//            'company',
            'candidates',
            'stores',
            'subCompanies',
            'totalCandidates'
        ];
    }
    
    public function getTotalCandidates() 
    {
        return parent::getTotalCandidateCount($this->company_id);
    }
        
    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getSubCompanyStores($modelClass = "\company\models\Store")
    {
        return parent::getSubCompanyStores($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getSubCompanies($modelClass = "\company\models\Company")
    {
        return parent::getSubCompanies($modelClass)->andWhere(['deleted'=>0]);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getStores($modelClass = "\company\models\Store")
    {
        return parent::getStores($modelClass)->andWhere(['deleted'=>0]);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getTransfers($modelClass = "\company\models\Transfer")
    {
        return parent::getTransfers($modelClass)->andWhere(['deleted'=>0]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = CompanyToken::find()->where(['token_value' => $token])->with('company')->one();
        if($token){
            return $token->company;
        }
    }
}
