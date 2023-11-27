<?php

namespace company\models;

use Yii;

/**
 * This is the model class for table "contact_invitation".
 * It extends from \common\models\ContactInvitation but with custom functionality for Contact Invitation module
 * 
 */
class ContactInvitation extends \common\models\ContactInvitation {

    /**
     * Sends an email to inform agent that he got invited
     * @return boolean whether the email was sent
     */
    public function sendInvitationEmail() {
        Yii::$app->mailer->htmlLayout = "layouts/text";

        $mailer = Yii::$app->mailer->compose('company/contact-invitation', [
                'model' => $this
            ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($this->email_to_invite)
            ->setSubject($this->company->company_name . " has invited you to collaborate in 
                        their recruitment process on StudentHub");

        try {
            $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }
    }

    public function sendAcceptedInvitationEmail() {

        if(!$this->contact->contact_email_verification)
            return false;

        $mailer = Yii::$app->mailer->compose('company/contact-invitation-acceptance', [
                            'model' => $this
                        ])
                        ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
                        ->setTo($this->contact->contact_email)
                        ->setSubject('Contact Invitation accepted');

        try {
            $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }
    }

    /**
     * Calls after saving object 
     * @param type $insert
     * @param type $changedAttributes
     */
    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);

        //need to send invitation only on insert 

        if (!$insert)
            return null;

        /**
         * if no account available with invited email, generate otp and 
         * send register page link 
         */
        if (!$this->invitedContact) {
            $this->generateOtp();
            $this->save(false);
        }
        
        $this->sendInvitationEmail();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitedContact($modelClass = '\company\models\Contact') {
        return parent::getInvitedContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = '\company\models\Contact') {
        return parent::getContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = '\company\models\Company') {
        return parent::getCompany($modelClass);
    }
}
