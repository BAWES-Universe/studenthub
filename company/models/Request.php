<?php
namespace company\models;

use Yii;
use common\models\MailLog;
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

    /**
     * @return bool|void
     */
    public function requestNotification()
    {
        $company_name = $this->company->company_common_name_en ? $this->company->company_common_name_en: $this->company->company_name;

        $staffList = \common\models\Staff::find()
            ->joinWith('staffNotifications')
            ->andWhere(['staff.deleted' => false, 'staff_notification' => true, 'permission' => "new-requests"])
            ->all();

        $subject =  $company_name." is looking to hire ".$this->request_position_title;

        $arrTo = ArrayHelper::map($staffList,'staff_email','staff_name');

        if(sizeof($arrTo) == 0) {
            return false;
        }

        foreach ($arrTo as $staff_email => $staff_name) {
            $ml = new MailLog();
            $ml->to = $staff_email;
            $ml->from = \Yii::$app->params['supportEmail'];
            $ml->subject = $subject;
            if (!$ml->save()) {
                Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
            }
        }

        $mailer = \Yii::$app->mailer->compose("company/request-created-bycompany",
            [
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "model" => $this,
            ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name])
            ->setTo($arrTo)
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
