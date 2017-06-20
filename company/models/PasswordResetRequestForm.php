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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => Company::className(), 'targetAttribute' => ['email' => 'company_email']],
        ];
    }

    /**
     * @param $company
     * @return bool
     */
    public function sendEmail($company)
    {
        $company->generatePasswordResetToken();
        $company->save();

        Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $company->company_name,
                "token" => $company->company_password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($company->company_email)
            ->setSubject('Password reset token')
            ->send();

        return true;
    }
}
