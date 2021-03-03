<?php

namespace staff\models;


class ContactEmail extends \common\models\ContactEmail
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\staff\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}
