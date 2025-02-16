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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => StoreManager::class, 'targetAttribute' => ['email' => 'email']],
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
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $contact->name,
                "token" => $contact->password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($contact->email)
            ->setSubject('Password reset token');

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

        return true;
    }
}
