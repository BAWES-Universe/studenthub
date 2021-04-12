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
            'company_common_name_en',
            'company_common_name_ar',
            'company_description_en',
            'company_description_en',
            'company_website',
            'company_approved_to_hire',
            'company_status'=> function($model) {

                if(
                    $this->total_candidate > 0 ||
                    $this->is_request_updates_in_30_days > 0 ||
                    $this->no_of_active_requests > 0
                ) {
                    return self::STATUS_ACTIVE;
                }

                return self::STATUS_INACTIVE;
            },
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\company\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\company\models\Request")
    {
        return parent::getRequests($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany($modelClass = "\company\models\Company")
    {
        return parent::getParentCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\company\models\Invoice")
    {
        parent::getInvoices($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getParentTransfers($modelClass = "\company\models\Transfer")
    {
        return parent::getParentTransfers($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrands($modelClass = "\company\models\Brand")
    {
        return parent::getBrands($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\company\models\CompanyContact")
    {
        return parent::getCompanyContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContacts($modelClass = "\company\models\Contact")
    {
        return parent::getContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles($modelClass = "\company\models\File")
    {
        return parent::getFiles($modelClass);
    }
}
