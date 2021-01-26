<?php
namespace company\models;

use Yii;
use company\models\CompanyContact;
use company\models\ContactToken;


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
       return parent::fields();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = ContactToken::find()->where(['token_value' => $token])->with('contact')->one();

        if($token) {
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

    /**
     * sign up process
     * @param bool $validate
     * @return $this|null
     */
    public function signUp($validate = false) {

        $this->setPassword($this->contact_password_hash);

        if ($this->save($validate)) {
            return $this;
        }

        return null;
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
            ->filterWhere(['in', 'role', [CompanyContact::ROLE_OWNER, CompanyContact::ROLE_HR]]);
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

    /**
     * Am I owner?
     * @param string $company_id
     * @return boolean
     */
    public function IsOwner($company_id) {
        return \common\models\CompanyContact::find()
            ->where([
                'company_id' => $company_id,
                'contact_uuid' => Yii::$app->user->getId(),
                'role' => CompanyContact::ROLE_OWNER
            ])
            ->exists();
    }
}
