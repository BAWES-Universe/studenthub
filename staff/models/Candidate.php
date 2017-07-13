<?php
namespace staff\models;

use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['candidate_auth_key'],
        $fields['candidate_password_hash'],
        $fields['candidate_password_reset_token'],
        $fields['candidate_created_at'],
        $fields['candidate_updated_at']);
        return $fields;
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            $this->approved = false; //mark as dirty to send to admin for review

            return true;
        }

        return false;
    }

    /** 
     * Send new password to customer 
     * @param staff\models\Customer $model
     * @param string $password
     */
    public static function passwordMail($model, $password)
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        Yii::$app->mailer->compose("candidate-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/img/studenthub-logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => 'StudentHub'])
            ->setTo($model->candidate_email)
            ->setSubject('Your internship account password has been reset')
            ->send();
    }

    /** 
     * Send welcome mail to customer 
     * @param staff\models\Customer $model
     * @param string $password
     */
    public static function welcomeMail($model, $password) 
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        Yii::$app->mailer->compose("candidate-register",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/img/studenthub-logo.png', true),
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => 'StudentHub'])
            ->setTo($model->candidate_email)
            ->setSubject('Welcome to the '.Yii::$app->name)
            ->send();
    }
}
