<?php

namespace admin\models;


class AdminToken extends \common\models\AdminToken
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin($modelClass = "\admin\models\Admin")
    {
        return parent::getAdmin($modelClass);
    }
}