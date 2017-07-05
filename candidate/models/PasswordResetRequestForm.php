<?php
namespace candidate\models;

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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => Candidate::className(), 'targetAttribute' => ['email' => 'candidate_email']],
        ];
    }

    /**
     * @param $candidate
     * @return bool
     */
    public function sendEmail($candidate)
    {
        $candidate->generatePasswordResetToken();
        $candidate->save(false);
        
        Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $candidate->candidate_name,
                "token" => $candidate->candidate_password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($candidate->candidate_email)
            ->setSubject('Password reset token')
            ->send();

        return true;
    }
}
