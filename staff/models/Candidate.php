<?php
namespace staff\models;

use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "Candidate".
 * It extends from \common\models\Candidate but with custom functionality for this application module
 */
class Candidate extends \common\models\Candidate {

    public $password = null;
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
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        
        return Yii::$app->mailer->compose("candidate-password",
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
     * @param bool $insert
     * @param array $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert) {
            return $this->sendWelcomeEmail();
        }
    }

    /**
     * send welcome mail
     * @return bool
     */
    public function sendWelcomeEmail(){
        $model = $this;
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        $password = $model->password;
        $this->password = null;
        return Yii::$app->mailer->compose("candidate-register",
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

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidate($modelClass);
    }

    /**
     * Update profile photo from temp s3 bucket
     * @return bool
     */
    public function updateUserImages() {

        try {
            $this->setProfileByUrl(Yii::$app->temporaryBucketResourceManager->getUrl($this->candidate_personal_photo), 'profile');
            $this->setProfileByUrl(Yii::$app->temporaryBucketResourceManager->getUrl($this->candidate_civil_photo_front), 'civil-front');
            $this->setProfileByUrl(Yii::$app->temporaryBucketResourceManager->getUrl($this->candidate_civil_photo_back), 'civil-back');
            return true;
        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));
            return false;
        }
    }

    /**
     * Set profile photo by url
     * @param string $url
     */
    public function setProfileByUrl($url, $type = 'profile') {

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic
        $this->deleteProfilePhotoFromCloudinary($type);

        try {
            $result = Yii::$app->cloudinaryManager->upload(
                $url, [
                    'public_id' => $this->returnPhotoTypeWithNewName($type, $filename)
                ]
            );

            if ($result) {
                if ($type == 'profile') {
                    return $this->candidate_personal_photo = basename($result['url']);
                } else if ($type == 'civil-front') {
                    return $this->candidate_civil_photo_front = basename($result['url']);
                } else if ($type == 'civil-back') {
                    return $this->candidate_civil_photo_back = basename($result['url']);
                }
            }

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');
            $this->addError($this->returnPhotoTypeAttr($type), Yii::t('app', 'Please try again.'));
            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');
            $this->addError($this->returnPhotoTypeAttr($type), Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * delete old profile photo from cloudinary
     * @return boolean
     */
    public function deleteProfilePhotoFromCloudinary($type = 'profile') {

        try {
            return Yii::$app->cloudinaryManager->delete($this->returnPhotoTypeWithUrl($type));

        } catch (\Cloudinary\Error $e) {
            Yii::error($e->getMessage(), 'candidate');
            return false;
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), 'candidate');
            return false;
        }
    }

    private function returnPhotoTypeWithUrl($type){
        if ($type == 'profile' && $this->candidate_personal_photo) {
            $url = "candidate-photo/" . $this->candidate_personal_photo;
        } else if ($type == 'civil-front' && $this->candidate_civil_photo_front) {
            $url = "candidate-photo/" . $this->candidate_civil_photo_front;
        } else if ($type == 'civil-back' && $this->candidate_civil_photo_back) {
            $url = "candidate-photo/" . $this->candidate_civil_photo_back;
        }
        return $url;
    }

    private function returnPhotoTypeWithNewName($type,$name){
        if ($type == 'profile' && $this->candidate_personal_photo) {
            $url = "candidate-photo/" . $name;
        } else if ($type == 'civil-front' && $this->candidate_civil_photo_front) {
            $url = "candidate-photo/" . $name;
        } else if ($type == 'civil-back' && $this->candidate_civil_photo_back) {
            $url = "candidate-photo/" . $name;
        }
        return $url;
    }

    private function returnPhotoTypeAttr($type){
        if ($type == 'profile') {
            $attr = 'candidate_personal_photo';
        } else if ($type == 'civil-front') {
            $attr = 'candidate_civil_photo_front';
        } else if ($type == 'civil-back') {
            $attr = 'candidate_civil_photo_back';
        }
        return $attr;
    }
}
