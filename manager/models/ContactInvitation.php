<?php

namespace manager\models;

use common\models\MailLog;
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

        $ml = new MailLog();
        $ml->to = $this->email_to_invite;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = $this->company->company_name . " has invited you to collaborate in 
                        their recruitment process on StudentHub";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose('company/contact-invitation', [
                'model' => $this
            ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($this->email_to_invite)
            ->setSubject($this->company->company_name . " has invited you to collaborate in 
                        their recruitment process on StudentHub");

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

    public function sendAcceptedInvitationEmail() {

        if(!$this->contact->contact_email_verification)
            return false;

        $ml = new MailLog();
        $ml->to = $this->contact->contact_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Contact Invitation accepted";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose('company/contact-invitation-acceptance', [
                            'model' => $this
                        ])
                        ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
                        ->setTo($this->contact->contact_email)
                        ->setSubject('Contact Invitation accepted');

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
    public function getInvitedContact($modelClass = '\manager\models\Contact') {
        return parent::getInvitedContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = '\manager\models\Contact') {
        return parent::getContact($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = '\manager\models\Company') {
        return parent::getCompany($modelClass);
    }
}
