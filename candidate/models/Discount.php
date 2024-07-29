<?php

namespace candidate\models;

class Discount extends \common\models\Discount
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory($modelClass = "\candidate\models\DiscountCategory")
    {
        return parent::getCategory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\candidate\models\Store")
    {
        return parent::getStore($modelClass);
    }
}