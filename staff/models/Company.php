<?php
namespace staff\models;

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
        $field = parent::fields();
        unset(
            $field['company_created_at'],
            $field['company_updated_at']
        );
        $field['total_candidates'] = function($model) {
            return self::getTotalCandidateCount($model->company_id);
        };
        return $field;
    }


    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSubCompanies($modelClass = "\staff\models\Company")
    {
        return parent::getSubCompanies($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getStores($modelClass = "\staff\models\Store")
    {
        return parent::getStores($modelClass)->andWhere(['deleted'=>0]);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\staff\models\Transfer")
    {
        return parent::getTransfers($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getParentTransfers($modelClass = "\staff\models\Transfer")
    {
        return parent::getParentTransfers($modelClass);
    }

}
