<?php

namespace candidate\models;


class Contact extends \common\models\Contact
{
    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset(
            $fields['contact_password_hash'],
            $fields['contact_receive_email'],
            $fields['contact_receive_notification'],
            $fields['contact_auth_key'],
            $fields['contact_password_reset_token']
        );

        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\candidate\models\CompanyContact")
    {
        return parent::getCompanyContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContactsHavingAccess($modelClass = "\candidate\models\CompanyContact")
    {
        return parent::getCompanyContactsHavingAccess($modelClass);
    }

    /**
     * list all parents companies where this contact is owner or HR
     * @return \yii\db\ActiveQuery
     */
    public function getManagedCompanies($modelClass = "\candidate\models\Company")
    {
        return parent::getManagedCompanies($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\candidate\models\Company")
    {
        return parent::getCompanies($modelClass);
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\candidate\models\Request")
    {
        return parent::getRequests($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\candidate\models\Note")
    {
        return parent::getNotes($modelClass);
    }
}
