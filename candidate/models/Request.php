<?php

namespace candidate\models;


class Request extends \common\models\Request
{

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['request_compensation'],
            $fields['request_number_of_employees'],
            $fields['request_additional_info']
        );

        // remove fields that contain sensitive information
        return $fields;
    }

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
    public function getRequestCreatedBy($modelClass = "\candidate\models\Staff")
    {
        return parent::getRequestCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\candidate\models\Staff")
    {
        return parent::getRequestUpdatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedByContact($modelClass = "\candidate\models\Contact")
    {
        return parent::getRequestCreatedByContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedByContact($modelClass = "\candidate\models\Contact")
    {
        return parent::getRequestUpdatedByContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastActivity($modelClass = "\candidate\models\Note")
    {
        return parent::getLastActivity($modelClass)
            ->filterNonInternal();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestActivities($modelClass = "\candidate\models\Note")
    {
        return parent::getRequestActivities($modelClass)
            ->filterNonInternal();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\candidate\models\Suggestion")
    {
        return parent::getSuggestions($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveSuggestions($modelClass = "\candidate\models\Suggestion")
    {
        return parent::getActiveSuggestions($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\candidate\models\Invitation") {
        return parent::getInvitations($modelClass);
    }
}
