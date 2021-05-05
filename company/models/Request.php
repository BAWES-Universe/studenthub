<?php
namespace company\models;


use staff\models\Staff;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "Request".
 * It extends from \common\models\Request but with custom functionality for this application module
 */
class Request extends \common\models\Request
{

    public function extraFields()
    {
        return array_merge(parent::extraFields(),[
            'stats'
        ]);
    }

    public function getStats() {
        return [
            'suggested' => $this->getSuggestions()->count(),
            'invited' => $this->getInvitations()->count(),
            'rejected' => $this->getInvitations()->rejected()->count(),
            'accepted' => $this->getInvitations()->accepted()->count(),
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\company\models\Contact")
    {
        return parent::getContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getRequestCreatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getRequestUpdatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getLastActivity($modelClass = "\company\models\Note")
    {
        return parent::getLastActivity($modelClass)
            ->filterNonInternal();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequestActivities($modelClass = "\company\models\Note")
    {
        return parent::getRequestActivities($modelClass)
            ->filterNonInternal();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\company\models\Suggestion") {
        return parent::getSuggestions($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getActiveSuggestions($modelClass = "\company\models\Suggestion") {
        return parent::getActiveSuggestions($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\company\models\Invitation") {
        return parent::getInvitations($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedByContact($modelClass = "\company\models\Contact")
    {
        return parent::getRequestCreatedByContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedByContact($modelClass = "\company\models\Contact")
    {
        return parent::getRequestUpdatedByContact($modelClass);
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $this->requestNotification();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function requestNotification()
    {
        $company_name = $this->company->company_common_name_en ? $this->company->company_common_name_en: $this->company->company_name;

        $staffList = Staff::findAll(['deleted'=>'0']);

        $subject =  $company_name." is looking to hire ".$this->request_position_title;

        return \Yii::$app->mailer->compose("company/request-created-bycompany",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "model" => $this,
            ])
            ->setFrom([\Yii::$app->user->identity->contact_email => \Yii::$app->user->identity->contact_name])
            ->setTo(ArrayHelper::map($staffList,'staff_email','staff_name'))
            ->setSubject($subject)
            ->send();
    }
}
