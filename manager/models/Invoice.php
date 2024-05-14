<?php
namespace manager\models;


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
        $fields = parent::fields();

        //company relation
        $fields['company'] = function($model) {
            return $model->transfer->company;
        };

        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\manager\models\Company")
    {
        return parent::getCompany ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\manager\models\Transfer")
    {
        return parent::getTransfer ($modelClass);
    }
}
