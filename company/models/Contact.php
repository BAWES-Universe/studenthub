<?php
namespace company\models;

use common\models\MailLog;
use Yii;
use yii\db\Expression;
use yii\web\NotFoundHttpException;


/**
 * This is the model class for table "Contact".
 * It extends from \common\models\Contact but with custom functionality for this application module
 */
class Contact extends \common\models\Contact implements \yii\web\IdentityInterface {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $authType = HttpBearerAuth::class, $type = ContactToken::STATUS_ACTIVE, $otp = null) {
        return parent::findIdentityByAccessToken($token, $authType, $type, $otp);
        /*$token = ContactToken::find()
            ->andWhere(['token_value' => $token])
            ->with('contact')
            ->one();

        if($token) {
            return $token->contact;
        }*/
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

        $ml = new MailLog();
        $ml->to = $this->contact_email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Reset your StudentHub password";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose("company/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->contact_email,
                "name" => $this->contact_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->contact_email)
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

    /**
     * sign up process
     * @param bool $validate
     * @return $this|null
     */
    public function signUp($validate = false) {

        if ($this->contact_password_hash) {
            $this->setPassword($this->contact_password_hash);
        }

        //$this->contact_status = self::STATUS_INACTIVE;

        if ($this->save($validate)) {

            if ($this->getScenario() == 'signup-google')
                $notification_message = "[Contact Signup: " . $this->contact_name . "] Signed up using Google";
            else if ($this->getScenario() == 'signupAuth0')
                $notification_message = "[Contact Signup: " . $this->contact_name . "] Signed up using Auth0";
            else
                $notification_message = "[Contact Signup: " . $this->contact_name . "] Signed up using Manual";

            Yii::info($notification_message , __METHOD__);

            if (!$this->contact_email_verification) {
                $this->sendVerificationEmail();
            }

            return $this;
        }

        return null;
    }

    /**
     * Sends an email requesting a user to verify his email address
     * @return boolean whether the email was sent
     */
    public function sendVerificationEmail() {

        $this->generateAuthKey();

        //Update contact last email limit timestamp
        $this->contact_limit_email = new Expression('NOW()');
        $this->save(false);

        if ($this->contact_new_email) {
            $email = $this->contact_new_email;
        } else {
            $email = $this->contact_email;
        }

        $ml = new MailLog();
        $ml->to = $email;
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = "Please confirm your email address";
        if (!$ml->save()) {
            Yii::error('Failed to save mail log :' . print_r($ml->errors, true));
        }

        $mailer = Yii::$app->mailer->compose([
            'html' => 'company/verify-email-html',
            'text' => 'company/verify-email-text',
        ], [
            'contact' => $this
        ])
            ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->params['appName']])
            ->setTo($email)
            ->setSubject('Please confirm your email address');

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
     * Verifies the candidate email
     */
    public static function verifyEmail($email, $code) {

        $model = Contact::find()
            ->andWhere([
                'AND',
                ['contact_auth_key' => $code],
                [
                    'OR',
                    ['contact_new_email' => $email],
                    ['contact_email' => $email]
                ]
            ])
            ->one();

        //to cope with sql case insensitivity

        if(!$model || $model->contact_auth_key != $code) {
            return false;
        }

        $model->setScenario('verifyEmail');

        //If not verified
        if ($model->contact_email_verification == Contact::EMAIL_NOT_VERIFIED) {
            //Verify this email
            $model->contact_email_verification = Contact::EMAIL_VERIFIED;
        }

        // new email address

        if (!empty($model->contact_new_email)) {
            $model->contact_email = $model->contact_new_email;
            $model->contact_new_email = null;
        }

        $model->contact_auth_key = ''; //remove auth key
        //$model->save(false);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByUnVerifiedTokenToken($token, $type = null) {
        $token = ContactToken::find()->andWhere(['token_value' => $token])
            ->with('contact')
            ->one();

        if ($token) {
            return $token->contact;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContacts($modelClass = "\company\models\CompanyContact")
    {
        return parent::getCompanyContacts($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContactsHavingAccess($modelClass = "\company\models\CompanyContact")
    {
        return $this->getCompanyContacts($modelClass)
            ->andWhere(['allow_access' => true]);
    }

    /**
     * list all parents companies where this contact is owner or HR
     * @return \yii\db\ActiveQuery
     */
    public function getManagedCompanies($modelClass = "\company\models\Company")
    {
        return parent::getManagedCompanies($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\company\models\Company")
    {
        return parent::getCompanies($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactEmails($modelClass = "\company\models\ContactEmail")
    {
        return parent::getContactEmails($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContactPhones($modelClass = "\company\models\ContactPhone")
    {
        return parent::getContactPhones($modelClass);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequests($modelClass = "\company\models\Request")
    {
        return parent::getRequests ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\company\models\Note")
    {
        return parent::getNotes ($modelClass);
    }

    /**
     * Start of IdentityInterface Methods
     */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['contact_id' => $id]);
    }

    /**
     * Finds company by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email) {
        return static::findOne(['contact_email' => $email]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'contact_password_reset_token' => $token
        ]);
    }
}
