<?php
namespace company\models;

use Yii;

/**
 * This is the model class for table "CompanyToken".
 * It extends from \common\models\CompanyToken but with custom functionality for this application module
 *
 */
class CompanyToken extends \common\models\CompanyToken {

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

}
