<?php

namespace candidate\models;


class CompanyContact extends \common\models\CompanyContact
{
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
    public function getContact($modelClass = "\candidate\models\Contact")
    {
        return parent::getContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\common\models\ContactEmail")
    {
        return parent::getContactEmails($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\common\models\ContactPhone")
    {
        return parent::getContactPhones($modelClass);
    }
}