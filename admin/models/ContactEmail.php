<?php

namespace admin\models;


class ContactEmail extends \common\models\ContactEmail
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}
