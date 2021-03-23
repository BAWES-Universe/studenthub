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
        return parent::getCompanyContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\staff\models\CompanyContact")
    {
        return parent::getCompanyContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContactsHavingAccess($modelClass = "\staff\models\CompanyContact")
    {
        return parent::getCompanyContactsHavingAccess($modelClass);
    }

    /**
     * list all parents companies where this contact is owner or HR
     * @return \yii\db\ActiveQuery
     */
    public function getManagedCompanies($modelClass = "\staff\models\Company")
    {
        return parent::getManagedCompanies($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\staff\models\Company")
    {
        return parent::getCompanies($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\staff\models\ContactEmail")
    {
        return parent::getContactEmails($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\staff\models\ContactPhone")
    {
        return parent::getContactPhones($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\staff\models\Request")
    {
        return parent::getRequests($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\staff\models\Note")
    {
        return parent::getNotes($modelClass);
    }
}
