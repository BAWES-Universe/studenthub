<?php
namespace staff\models;

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
            [['email'], 'exist', 'skipOnError' => false, 'targetClass' => Staff::class, 'targetAttribute' => ['email' => 'staff_email']],
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
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("passwordResetRequest",
            [
                "name" => $staff->staff_name,
                "token" => $staff->staff_password_reset_token,
            ])
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($staff->staff_email)
            ->setSubject('Password reset token');

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            $mailer->send();
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
