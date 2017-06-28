<?php
namespace company\models;

use Yii;
use company\models\Candidate;
use yii\helpers\ArrayHelper;

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
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates()
    {
        if($this->parent_company_id)
        {
            //for child company
            return $this->hasMany(Candidate::className(), ['store_id' => 'store_id'])
                ->via('stores');
        } else {
            //for parent company
            return $this->hasMany(Candidate::className(), ['store_id' => 'store_id'])
                ->via('subCompanyStores')
                ->where(['{{%candidate}}.deleted' => 0]);
        }        
    }

    public function getSubCompanyStores() 
    {
        return $this->hasMany(Store::className(), ['company_id' => 'company_id'])
            ->via('subCompanies')
            ->where(['deleted'=>0]);
    }            

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies()
    {
        return $this->hasMany(Company::className(), ['parent_company_id' => 'company_id'])->where(['deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores()
    {
        return $this->hasMany(Store::className(), ['company_id' => 'company_id'])->where(['deleted'=>0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers()
    {
        return $this->hasMany(Transfer::className(), ['company_id' => 'company_id'])->where(['deleted'=>0]);
    }

    /**
     * @param mixed $token
     * @param null $type
     * @return mixed
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = CompanyToken::find()->where(['token_value' => $token])->with('company')->one();
        if($token){
            return $token->company;
        }
    }

}
