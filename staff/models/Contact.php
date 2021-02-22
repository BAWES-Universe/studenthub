<?php

namespace staff\models;

class Contact extends \common\models\Contact
{
    /**
     * @return \yii\db\ActiveQuery
     * to use with single company
     */
    public function getCompanyContact($modelClass = "\staff\models\CompanyContact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }
}
