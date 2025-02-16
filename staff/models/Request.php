<?php

namespace staff\models;

use common\models\MailLog;
use Yii;
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
        $fields['invited'] = function($model) {
            return $model->getInvitations()->count();
        };
        $fields['suggestion'] = function($model) {
            return $model->getSuggestions()->count();
        };

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

    /**
     * @param $insert
     * @param $changedAttributes
     * @return void
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            $this->requestUpdateNotification($changedAttributes);
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @param $changedAttributes
     * @return bool|void
     */
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

        if (array_key_exists('request_status', $changedAttributes)) {
            $oldStatus = ucfirst(str_replace('_',' ',$changedAttributes['request_status']));
            $newStatus = ucfirst(str_replace('_',' ',$this->request_status));
            array_push($changedParam,'Request status changed from "'.$oldStatus.'" to "'.$newStatus.'"');
        }

        if (count($changedParam)  == 0) {
            return;
        }

        $company_name = $this->company->company_common_name_en ? $this->company->company_common_name_en: $this->company->company_name;

        $staffList = Staff::find()
            ->joinWith('staffNotifications')
            ->andWhere(['staff.deleted' => false, 'staff.staff_notification' => true, 'permission' => "request-updates"])
            ->andWhere(['!=', 'staff.staff_id', \Yii::$app->user->id])
            ->all();

        $subject =  "I've updated the request for ".$this->request_position_title." for ".$company_name;

        $arrEmails = ArrayHelper::map($staffList,'staff_email','staff_name');

        if (sizeof($arrEmails) == 0) {
            return false;
        }

        foreach ($arrEmails as $email => $staff) {
            $ml = new MailLog();
            $ml->to = $email;
            $ml->from = \Yii::$app->params['supportEmail'];
            $ml->subject = $subject;
            if (!$ml->save()) {
                Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
            }
        }

        $mailer = \Yii::$app->mailer->compose("company/request-updated",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "model" => $this,
                "changedParam" => $changedParam,
                "changedAttributes" => $changedAttributes,
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setReplyTo([\Yii::$app->user->identity->staff_email => \Yii::$app->user->identity->staff_name])
            ->setTo($arrEmails)
            ->setSubject($subject);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * @return bool|void
     */
    public function requestNotification()
    {
        $company_name = $this->company->company_common_name_en ? $this->company->company_common_name_en: $this->company->company_name;

        $staffList = Staff::find()
            ->andWhere(['!=', 'staff_id', \Yii::$app->user->id])
            ->andWhere(['!=', 'staff.deleted', 1])
            ->andWhere(['staff_notification' => 1])
            ->all();

        $subject =  "I've added a request for ".$this->request_position_title." for ".$company_name;

        $arrEmails = ArrayHelper::map($staffList,'staff_email','staff_name');

        if (sizeof($arrEmails) == 0) {
            return false;
        }

        foreach ($arrEmails as $email => $staff) {
            $ml = new MailLog();
            $ml->to = $email;
            $ml->from = \Yii::$app->params['supportEmail'];
            $ml->subject = $subject;
            if (!$ml->save()) {
                Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
            }
        }

        $mailer = \Yii::$app->mailer->compose("company/request-created",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "model" => $this,
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setReplyTo([\Yii::$app->user->identity->staff_email => \Yii::$app->user->identity->staff_name])
            ->setTo($arrEmails)
            ->setSubject($subject);

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }
}
