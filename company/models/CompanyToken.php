<?php
namespace company\models;

/**
 * This is the model class for table "CompanyToken".
 * It extends from \common\models\CompanyToken but with custom functionality for this application module
 *
 */
class CompanyToken extends \common\models\CompanyToken {

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }
}
