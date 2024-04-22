<?php

namespace manager\models;


class CompanyContact extends \common\models\CompanyContact
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\manager\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\manager\models\Contact")
    {
        return parent::getContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\manager\models\ContactEmail")
    {
        return parent::getContactEmails($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\manager\models\ContactPhone")
    {
        return parent::getContactPhones($modelClass);
    }
}
