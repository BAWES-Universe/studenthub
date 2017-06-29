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
     * @param mixed $token
     * @param null $type
     * @return mixed
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        return parent::findIdentityByAccessToken($token, $type);
    }
}
