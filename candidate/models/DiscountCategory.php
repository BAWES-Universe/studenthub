<?php

namespace candidate\models;

class DiscountCategory extends \common\models\DiscountCategory
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDiscounts($modelClass = "\candidate\models\Discount")
    {
        return parent::getDiscounts($modelClass);
    }
}