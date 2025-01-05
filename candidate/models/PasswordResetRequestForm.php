<?php
namespace candidate\models;


use common\models\MailLog;
use Yii;
use yii\base\Model;

/**
 * Password Reset Request Form 
 */
class PasswordResetRequestForm extends Model
{
    public $email;
    public $phone_number;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'phone_number'], 'validateAnyOne'],
            [
                ['phone_number'],
                'number',
                'numberPattern' => '/^\d{8}$/',
                'message' => Yii::t('app', "Phone must be 8 digit number")
            ],
            [['email'], 'email'],
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => Candidate::class, 'targetAttribute' => ['email' => 'candidate_email']],
        ];
    }
    public function validateAnyOne($attribute) {

        if(!$this->email && !$this->phone_number) {
            $this->addError('email', Yii::t('app',
                'email or phone number required!'));
        }
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

        $ml = new MailLog();
        $ml->to = $candidate->candidate_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Password reset token';
        $ml->save();

        $mailer = Yii::$app->mailer->compose("passwordResetRequest",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $candidate->candidate_email,
                "name" => $candidate->candidate_name,
                "token" => $candidate->candidate_password_reset_token,
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($candidate->candidate_email)
            ->setSubject('Password reset token');

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "password-reset-token");
        }

        return true;
    }
}
