<?php

namespace admin\models;


class ContactPhone extends \common\models\ContactPhone
{

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}