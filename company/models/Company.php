<?php
namespace company\models;

use Yii;

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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers()
    {
        return $this->hasMany(Transfer::className(), ['company_id' => 'company_id']);
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
