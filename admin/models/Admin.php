<?php
namespace admin\models;

use common\models\AdminToken;
use common\models\MailLog;
use Yii;
use yii\db\Exception;
use yii\helpers\Url;

/**
 * This is the model class for table "Admin".
 * It extends from \common\models\Admin but with custom functionality for this application module
 */
class Admin extends \common\models\Admin {

    /**
     * Send new password to customer
     * @param Admin $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        /*if(!str_contains($model->admin_email, "bawes.net")) {
            return false;
        }*/

        $ml = new MailLog();
        $ml->to = $model->admin_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = 'Your account password has been reset';
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $mailer = Yii::$app->mailer->compose("admin-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->admin_email)
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
    public function getAccessTokens($modelClass = "\admin\models\AdminToken")
    {
        return parent::getAccessTokens($modelClass);
    }
}
