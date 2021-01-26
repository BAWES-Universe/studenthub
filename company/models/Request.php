<?php
namespace company\models;


/**
 * This is the model class for table "Request".
 * It extends from \common\models\Request but with custom functionality for this application module
 */
class Request extends \common\models\Request
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
    public function getContact($modelClass = "\company\models\CompanyContact")
    {
        return parent::getContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getRequestCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getRequestUpdatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastActivity($modelClass = "\company\models\Note")
    {
        return parent::getLastActivity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestActivities($modelClass = "\company\models\Note")
    {
        return parent::getRequestActivities($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\company\models\Suggestion") {
        return parent::getSuggestions($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveSuggestions($modelClass = "\company\models\Suggestion") {
        return parent::getActiveSuggestions($modelClass);
    }
}
