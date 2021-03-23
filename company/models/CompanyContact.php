<?php

namespace company\models;


class CompanyContact extends \common\models\CompanyContact
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\company\models\Contact")
    {
        return parent::getContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\company\models\ContactEmail")
    {
        return parent::getContactEmails($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\company\models\ContactPhone")
    {
        return parent::getContactPhones($modelClass);
    }
}
