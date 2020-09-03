<?php
namespace admin\models;

use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "Admin".
 * It extends from \common\models\Admin but with custom functionality for this application module
 */
class Admin extends \common\models\Admin {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['admin_auth_key'],
        $fields['admin_password_hash'],
        $fields['admin_password_reset_token']);

        return $fields;
    }

    /**
     * Send new password to customer
     * @param Admin $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';

        return Yii::$app->mailer->compose("admin-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->admin_email)
            ->setSubject('Your account password has been reset')
            ->send();
    }
}
