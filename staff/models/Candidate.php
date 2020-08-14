<?php
namespace staff\models;

use common\models\CandidateExperience;
use common\models\CandidateSkill;
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

            if (
                ($this->candidate_personal_photo && $this->candidate_personal_photo != $this->oldAttributes['candidate_personal_photo']) &&
                !$this->changeProfilePhoto()
            ) {
                return false;
            }

            if (
                ($this->candidate_resume && $this->candidate_resume != $this->oldAttributes['candidate_resume']) &&
                !$this->updateResume()
            ) {
                return false;
            }

            if (
                ($this->candidate_civil_photo_front && $this->candidate_civil_photo_front != $this->oldAttributes['candidate_civil_photo_front']) &&
                !$this->updateCivilId('front')
            ) {
                return false;
            }

            if (
                ($this->candidate_civil_photo_back && $this->candidate_civil_photo_back != $this->oldAttributes['candidate_civil_photo_back']) &&
                !$this->updateCivilId('back')
            ) {
                return false;
            }

            return true;
        }


        return false;
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
     * update candidate experiences
     * @param $experiences
     * @return array|bool
     */
    public function updateExperiences($experiences)
    {
        $experiences = explode(',', $experiences);

        if (empty($experiences) || count($experiences) == 0)
        {
            return ;
        }

        CandidateExperience::deleteAll([
            'candidate_id' => $this->candidate_id
        ]);

        foreach ($experiences as $experience) {
            if (!empty($experience)) {
                $model = new CandidateExperience;
                $model->candidate_id = $this->candidate_id;
                $model->experience = $experience;

                if(!$model->save()) {
                    return [
                        "operation" => "error",
                        "message" => $model->getErrors()
                    ];
                }
            }
        }
        return true;
    }

    /**
     * update candidate skills
     * @param $skills
     * @return array|bool
     */
    public function updateSkills($skills)
    {
        $skills_array = explode(',',$skills);

        if (empty($skills) || count($skills_array) == 0)
        {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Skills Required")
            ];
        }

        CandidateSkill::deleteAll([
            'candidate_id' => $this->candidate_id
        ]);

        foreach ($skills_array as $skill) {
            if (!empty($skill)) {
                $model = new CandidateSkill;
                $model->candidate_id = $this->candidate_id;
                $model->skill = $skill;

                if(!$model->save()) {
                    return [
                        "operation" => "error",
                        "message" => $model->getErrors()
                    ];
                }
            }
        }
        return true;
    }

    public function changeProfilePhoto()
    {
        try {
            $url = Yii::$app->temporaryBucketResourceManager->getUrl($this->candidate_personal_photo);

            return $this->setProfileByUrl($url);

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
    public function setProfileByUrl($url) {

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic

        if ($this->oldAttributes['candidate_personal_photo']) {
            $this->deleteProfilePhotoFromCloudinary();
        }

        try {
            $result = Yii::$app->cloudinaryManager->upload(
                $url, [
                    'public_id' => "candidate-photo/" . $filename
                ]
            );

            if ($result) {
                return $this->candidate_personal_photo = basename($result['url']);

            }

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_personal_photo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * delete old profile photo from cloudinary
     * @return boolean
     */
    public function deleteProfilePhotoFromCloudinary() {

        try {

            Yii::$app->cloudinaryManager->delete("candidate-photo/" . $this->oldAttributes['candidate_personal_photo']);

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'candidate');

            //$this->addError('profile_photo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            //$this->addError('profile_photo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * delete file from aws
     * @param string $type
     * @param string $side
     * @return false
     */
    public function deleteFile($type = 'resume', $side = 'front') {

        try {
            if ($type == 'resume') {
                $file = "candidate-resume/" . $this->oldPrimaryKey['candidate_resume'];
            } if ($type == 'civil-id' && $side == 'front') {
                $file = "candidate-civil-id/" . $this->oldPrimaryKey['candidate_civil_photo_front'];
            } else {
                $file = "candidate-civil-id/" . $this->oldPrimaryKey['candidate_civil_photo_back'];
            }
            Yii::$app->resourceManager->delete($file);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'file not available to delete.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'file not available to delete.'));

            return false;
        }
    }

    /**
     * @return bool
     */
    public function updateResume() {

        if ($this->oldAttributes['candidate_resume']) {
            $this->deleteFile('resume');
        }

        $fileName = $this->candidate_resume;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        $targetPath = "candidate-resume/" . $fileName;

        // Copy using S3ResourceManager Component
        try {

            return Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError('candidate_resume', Yii::t('app', 'Resume not available to save.'));

            return false;
        }
    }

    /**
     * @return bool
     */
    public function updateCivilId($side = 'front') {

        $idSide = ($side == 'front') ? 'candidate_civil_photo_front' : 'candidate_civil_photo_back';

        if ($this->oldAttributes[$idSide]) {
            $this->deleteFile('civil-id', $side);
        }

        $fileName = $this->$idSide;

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        $targetPath = "photos/" . $fileName;

        // Copy using S3ResourceManager Component
        try {

            return Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        } catch (\Aws\S3\Exception\S3Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError($idSide, Yii::t('app', 'file not available to save.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'candidate');

            $this->addError($idSide, Yii::t('app', 'file not available to save.'));

            return false;
        }
    }
}
