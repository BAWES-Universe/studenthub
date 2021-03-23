<?php
namespace admin\models;


/**
 * This is the model class for table "Invoice".
 * It extends from \common\models\Invoice but with custom functionality for this application module
 */
class Invoice extends \common\models\Invoice {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return parent::fields();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\admin\models\Transfer")
    {
        return parent::getTransfer ($modelClass);
    }
}
