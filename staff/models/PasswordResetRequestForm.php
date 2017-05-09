<?php
namespace staff\models;

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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => Staff::className(), 'targetAttribute' => ['email' => 'staff_email']],
        ];
    }

    /**
     * Send password reset token to staff email
     *
     * @param staff\models\staff $staff
     */
    public function sendEmail($staff)
    {
        $staff->generatePasswordResetToken();
        $staff->save();

        Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $staff->staff_name,
                "token" => $staff->staff_password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($staff->staff_email)
            ->setSubject('Password reset token')
            ->send();

        return true;
    }
}
