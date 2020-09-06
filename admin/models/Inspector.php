<?php
namespace admin\models;

use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "Inspector".
 * It extends from \common\models\Inspector but with custom functionality for this application module
 */
class Inspector extends \common\models\Inspector {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['inspector_auth_key'],
        $fields['inspector_password_hash'],
        $fields['inspector_password_reset_token']);

        return $fields;
    }

    /**
     * Send new password to customer
     * @param Inspector $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';

        return Yii::$app->mailer->compose("inspector-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->inspector_email)
            ->setSubject('Your account password has been reset')
            ->send();
    }
}
