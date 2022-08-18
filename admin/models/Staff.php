<?php
namespace admin\models;

use Yii;
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
            return \staff\models\Staff::decryptPass($model->staff_gmail_password);
        };
        $fields['total_assigned'] = function ($model) {
            return $model->getCandidateWorkHistories()->count();
        };
        $fields['total_requests'] = function ($model) {
            return $model->getRequests()->count();
        };

        $fields['total_notes'] = function ($model) {
            return $model->getNotes()->count();
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
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        
        return Yii::$app->mailer->compose("staff-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->staff_email)
            ->setSubject('Your account password has been reset')
            ->send();
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
}
