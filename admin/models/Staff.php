<?php
namespace admin\models;

use common\models\MailLog;
use Yii;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * This is the model class for table "Staff".
 * It extends from \common\models\Staff but with custom functionality for this application module
 */
class Staff extends \common\models\Staff {

    /**
     * @return array|string[]
     */
    public function extraFields()
    {
        return array_merge(
            [
                'staffSalaries',
                'staffNotifications',
                'totalAssigned',
                'totalRequests',
                'totalNotes',
                'totalStories',
                'totalAcceptedInvitations',
                'totalRejectedInvitations',
                'totalSuggestions',
                'companies'
            ],
            parent::extraFields()
        );
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['staff_auth_key'],
        $fields['staff_password_hash'],
        $fields['staff_password_reset_token']);

        $fields['staff_gmail_password'] = function ($model) {
            if($model->staff_gmail_password) {
                return \staff\models\Staff::decryptPass($model->staff_gmail_password);
            }
            return null;
        };

        return $fields;
    }
    
    /**
     * Send new password to customer
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        $ml = new MailLog();
        $ml->to = $model->staff_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Your account password has been reset';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';
        
        $mailer = Yii::$app->mailer->compose("staff-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->staff_email)
            ->setSubject('Your account password has been reset');

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
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\StaffToken")
    {
        return parent::getAccessTokens($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\admin\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\admin\models\Company")
    {
        return parent::getCompanies($modelClass);
    }

    /**
     * @return query\StaffQuery
     */
    public static function find()
    {
        return new query\StaffQuery(get_called_class());
    }

    /**
     * @return bool|void
     */
    public function sendVerificationEmail() {

        $ml = new MailLog();
        $ml->to = $this->staff_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Reset your StudentHub password';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $webUrl = Yii::$app->params['staffAppUrl'] . 'update-password/' . $this->staff_password_reset_token;

        $mailer = Yii::$app->mailer->compose("staff/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->staff_email,
                "name" => $this->staff_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->staff_email)
            ->setSubject('Reset your StudentHub password');

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
    }
}
