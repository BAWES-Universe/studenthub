<?php

namespace staff\models;


use yii\helpers\ArrayHelper;


/**
 * This is the model class for table "Request".
 * It extends from \common\models\Request but with custom functionality for this application module
 */
class Request extends \common\models\Request {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getRequestCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getRequestUpdatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\staff\models\Contact")
    {
        return parent::getContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedByContact($modelClass = "\staff\models\Contact")
    {
        return parent::getRequestCreatedByContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedByContact($modelClass = "\staff\models\Contact")
    {
        return parent::getRequestUpdatedByContact($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastActivity($modelClass = "\staff\models\Note")
    {
        return parent::getLastActivity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestActivities($modelClass = "\staff\models\Note")
    {
        return parent::getRequestActivities($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestions($modelClass = "\staff\models\Suggestion") {
        return parent::getSuggestions($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\staff\models\Invitation") {
        return parent::getInvitations($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveSuggestions($modelClass = "\staff\models\Suggestion") {
        return parent::getActiveSuggestions($modelClass);
    }

    public function afterSave($insert, $changedAttributes)
    {
        $this->requestUpdateNotification($changedAttributes);
        parent::afterSave($insert, $changedAttributes);
    }

    public function requestUpdateNotification($changedAttributes)
    {
        $changedParam = [];

         if (array_key_exists('request_position_type', $changedAttributes)) {
             $old = ($changedAttributes['request_position_type'] == 1) ? 'full-time':'part-time';
             $new = ($this->request_position_type == 1) ? 'full-time':'part-time';
             array_push($changedParam,'Changed position type from "'.$old.'" to "'.$new.'"');
         }

         if (array_key_exists('request_position_title', $changedAttributes)) {
             array_push($changedParam,'Changed position title from "'.$changedAttributes['request_position_title'].'" to "'.$this->request_position_title.'"');
         }

         if (array_key_exists('request_job_description', $changedAttributes)) {
             array_push($changedParam,'Changed description from "'.$changedAttributes['request_job_description'].'" to "'.$this->request_job_description.'"');
         }

        if (array_key_exists('request_compensation', $changedAttributes)) {
            array_push($changedParam,'Changed compensation from "'.$changedAttributes['request_compensation'].'" to "'.$this->request_compensation.'"');
         }

        if (array_key_exists('request_number_of_employees', $changedAttributes)) {
            array_push($changedParam,'Changed compensation from "'.$changedAttributes['request_number_of_employees'].'" to "'.$this->request_number_of_employees.'"');
        }

        if (array_key_exists('request_location', $changedAttributes)) {
            array_push($changedParam,'Changed location from "'.$changedAttributes['request_location'].'" to "'.$this->request_location.'"');
        }

        if (array_key_exists('request_additional_info', $changedAttributes)) {
            array_push($changedParam,'Changed addition info from "'.$changedAttributes['request_additional_info'].'" to "'.$this->request_additional_info.'"');
        }

        if (count($changedParam)  == 0) {
            return;
        }

        $company_name = $this->company->company_common_name_en ? $this->company->company_common_name_en: $this->company->company_name;

        $staffList = Staff::find()
            ->andWhere(['!=', 'staff_id', \Yii::$app->user->id])
            ->all();
        $subject =  "I've updated the request for ".$this->request_position_title." for ".$company_name;


        return \Yii::$app->mailer->compose("company/request-updated",
            [
                "logo" => \Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                "model" => $this,
                "changedParam" => $changedParam,
                "changedAttributes" => $changedAttributes,
            ])
            ->setFrom([\Yii::$app->user->identity->staff_email => \Yii::$app->user->identity->staff_name])
            ->setTo(ArrayHelper::map($staffList,'staff_email','staff_name'))
            ->setSubject($subject)
            ->send();
    }

    public function requestNotification()
    {
        $company_name = $this->company->company_common_name_en ? $this->company->company_common_name_en: $this->company->company_name;

        $staffList = Staff::find()
            ->andWhere(['!=', 'staff_id', \Yii::$app->user->id])
            ->all();

        $subject =  "I've added a request for ".$this->request_position_title." for ".$company_name;

        return \Yii::$app->mailer->compose("company/request-created",
            [
                "logo" => \Yii::$app->urlManagerStaff->createAbsoluteUrl('../images/logo.png', 'https'),
                "model" => $this,
            ])
            ->setFrom([\Yii::$app->user->identity->staff_email => \Yii::$app->user->identity->staff_name])
            ->setTo(ArrayHelper::map($staffList,'staff_email','staff_name'))
            ->setSubject($subject)
            ->send();
    }
}
