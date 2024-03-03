<?php
namespace inspector\models;

use common\models\MailLog;
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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => \common\models\Inspector::className(), 'targetAttribute' => ['email' => 'inspector_email']],
        ];
    }

    /**
     * @param $staff
     * @return bool
     */
    public static function sendEmail($staff)
    {
        $staff->generatePasswordResetToken();
        $staff->save();

        $ml = new MailLog();
        $ml->to = $staff->staff_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Password reset token";
        $ml->save();

        $mailer = Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $staff->staff_name,
                "token" => $staff->staff_password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($staff->staff_email)
            ->setSubject('Password reset token');

        try {
            $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }

        return true;
    }
}
