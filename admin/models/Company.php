<?php
namespace admin\models;


use yii\db\Expression;

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
            },
            'total_suggestions' => function($model) {
                return $model->getSuggestions()->count();
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
        return parent::getNotes($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\admin\models\Request")
    {
        return parent::getRequests($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany($modelClass = "\admin\models\Company")
    {
        return parent::getParentCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidates ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\admin\models\Invoice")
    {
        parent::getInvoices($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\admin\models\Transfer")
    {
        return parent::getTransfers($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getParentTransfers($modelClass = "\admin\models\Transfer")
    {
        return parent::getParentTransfers($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \common\models\Company
     */
    public function getSubCompanyStores($modelClass = "\admin\models\Store")
    {
        return parent::getSubCompanyStores($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrands($modelClass = "\common\models\Brand")
    {
        return parent::getBrands($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\common\models\CompanyContact")
    {
        return parent::getCompanyContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContacts($modelClass = "\common\models\Contact")
    {
        return parent::getContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles($modelClass = "\common\models\File")
    {
        return parent::getFiles($modelClass);
    }

    public static function getCompanyByCondition($condition = null, $startDate = null, $endDate = null) {
        $query = Company::find()
            ->filterParent();

        if ($condition == 'status') {
            $query->filterActive();
        }
        $query->notDeleted();
        if($startDate) {
            $query->andWhere(new Expression("DATE(company_created_at) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $query->andWhere(new Expression("DATE(company_created_at) <= DATE('" . $endDate . "')"));
        }

        return $query->count();
    }

    public static function request($status = null, $startDate = null, $endDate = null) {
        $query = Request::find();

        if ($status) {
            $query->filterByStatus($status);
        }
        if($startDate) {
            $query->andWhere(new Expression("DATE(request_created_datetime) >= DATE('" . $startDate . "')"));
        }

        if($endDate) {
            $query->andWhere(new Expression("DATE(request_created_datetime) <= DATE('" . $endDate . "')"));
        }
        return $query->count();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\admin\models\Staff")
    {
        return parent::getStaff($modelClass);
    }
}
