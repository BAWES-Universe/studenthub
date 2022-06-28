<?php

namespace admin\models;


class Expense extends \common\models\Expense
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\admin\models\Admin")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\admin\models\Admin")
    {
        return parent::getUpdatedBy($modelClass);
    }
}