<?php
namespace manager\models;

use common\models\MailLog;
use common\models\StoreManager;
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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => StoreManager::className(), 'targetAttribute' => ['email' => 'email']],
        ];
    }

    /**
     * @param $company
     * @return bool
     */
    public function sendEmail($contact)
    {
        if(!$contact->email_verification)
            return false;

        $contact->generatePasswordResetToken();
        $contact->save();

        $ml = new MailLog();
        $ml->to = $contact->email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Password reset token";
        $ml->save();

        $mailer = Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $contact->name,
                "token" => $contact->password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($contact->email)
            ->setSubject('Password reset token');

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }

        return true;
    }
}
