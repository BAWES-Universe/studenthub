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
        
        $webUrl = Yii::$app->params['candidateAppUrl'] . 'update-password/' . $candidate->candidate_auth_key;

        Yii::$app->mailer->compose("passwordResetRequest",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $candidate->candidate_email,
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
