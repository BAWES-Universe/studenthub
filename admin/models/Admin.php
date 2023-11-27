<?php
namespace admin\models;

use common\models\AdminToken;
use Yii;
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

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
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
