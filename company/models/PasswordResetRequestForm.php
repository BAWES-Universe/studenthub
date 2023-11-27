<?php
namespace company\models;

use Yii;
use yii\base\Model;


/**
 * Password Reset Request Form 
 */
class PasswordResetRequestForm extends Model
{
    public $email;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email'], 'required'],
            [['email'], 'email'],
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => Contact::className(), 'targetAttribute' => ['email' => 'contact_email']],
        ];
    }

    /**
     * @param $company
     * @return bool
     */
    public function sendEmail($contact)
    {
        if(!$contact->contact_email_verification)
            return false;

        $contact->generatePasswordResetToken();
        $contact->save();

        $mailer = Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $contact->contact_name,
                "token" => $contact->contact_password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($contact->contact_email)
            ->setSubject('Password reset token');

        try {
            $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }

        return true;
    }
}
