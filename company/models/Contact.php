<?php
namespace company\models;
use Yii;
use common\models\ContactToken;

/**
 * This is the model class for table "Contact".
 * It extends from \common\models\Contact but with custom functionality for this application module
 */
class Contact extends \common\models\Contact {

    /**
     * @return array
     */
    public function fields()
    {
       return parent::fields();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = ContactToken::find()->where(['token_value' => $token])->with('contact')->one();
        if($token){
            return $token->contact;
        }
    }

    /**
     * Send link in email to reset password
     * @return bool
     */
    public function sendPasswordResetEmail()
    {
        $this->generatePasswordResetToken();
        $this->save(false);

        //Yii::$app->mailer->htmlLayout = 'layouts/html';

        $webUrl = Yii::$app->params['companyAppUrl'] . 'update-password/' . $this->contact_password_reset_token;

        return Yii::$app->mailer->compose("company/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->contact_email,
                "name" => $this->contact_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->contact_email)
            ->setSubject('Reset your StudentHub password')
            ->send();
    }
}
