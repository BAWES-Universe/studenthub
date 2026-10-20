<?php
namespace staff\models;

use common\models\CandidateExperience;
use common\models\CandidateSkill;
use common\models\CandidateTag;
use common\models\Suggestion;
use Yii;


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
        $fields['candidate_password_reset_token']);
        $fields['candidate_name'] = function($model){
            return strtolower($model->candidate_name);
        };

        $fields['candidate_personal_photo_url'] = function ($model) {
            return $model->getPersonalPhotoUrl();
        };

        return $fields;
    }

    /**
     * @return array|string[]
     */
    public function extraFields()
    {
        $fields = parent::extraFields ();

        return array_merge ([
            'invited',
            'invitationAccepted',
            'invitationRejected',
            'suggested',
            'suggestionAccepted',
            'suggestionRejected',
            "currentWorkHistory"
        ], $fields);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvitations($modelClass = "\staff\models\Invitation")
    {
        return parent::getInvitations($modelClass);
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPaidTransferCandidate($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\staff\models\University")
    {
        return parent::getUniversity($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\staff\models\Country")
    {
        return parent::getCountry($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\staff\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCard($modelClass = "\staff\models\CandidateIdCard")
    {
        return parent::getCandidateIdCard($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateIdCards($modelClass = "\staff\models\CandidateIdCard")
    {
        return parent::getCandidateIdCards($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateSkills($modelClass = "\common\models\CandidateSkill")
    {
        return parent::getCandidateSkills($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateExperiences($modelClass = "\common\models\CandidateExperience")
    {
        return parent::getCandidateExperiences($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\staff\models\Country")
    {
        return parent::getNationality($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\staff\models\Bank")
    {
        return parent::getBank ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\staff\models\Area")
    {
        return parent::getArea ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\staff\models\Transfer")
    {
        return parent::getTransfers ($modelClass);
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\CandidateToken")
    {
        return parent::getAccessTokens ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkHistory($modelClass = "\staff\models\CandidateWorkHistory")
    {
        return parent::getWorkHistory ($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\staff\models\Suggestion")
    {
        return parent::getSuggestion ($modelClass);
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if($insert) {

            if (
                $this->candidate_personal_photo
                && $this->candidate_personal_photo != ($this->oldAttributes['candidate_personal_photo'] ?? null)
                && !$this->updatePersonalPhoto()
            ) {
                return false;
            }

            if ($this->candidate_resume && !$this->updateResume()) {
                return false;
            }

            if ($this->candidate_civil_photo_front && !$this->updateCivilId('front')) {
                return false;
            }

            if ($this->candidate_civil_photo_back && !$this->updateCivilId('back')) {
                return false;
            }
        }
        else 
        {
            if (
                isset($this->oldAttributes['candidate_personal_photo']) &&
                $this->candidate_personal_photo != $this->oldAttributes['candidate_personal_photo'] &&
                !$this->updatePersonalPhoto()
            ) {
                return false;
            }

            if (
                isset($this->oldAttributes['candidate_resume']) && 
                $this->candidate_resume != $this->oldAttributes['candidate_resume'] &&
                !$this->updateResume()
            ) {
                return false;
            }

            if (
                isset($this->oldAttributes['candidate_civil_photo_front']) && 
                $this->candidate_civil_photo_front != $this->oldAttributes['candidate_civil_photo_front'] &&
                !$this->updateCivilId('front')
            ) {
                return false;
            }

            if (
                isset($this->oldAttributes['candidate_civil_photo_back']) && 
                $this->candidate_civil_photo_back != $this->oldAttributes['candidate_civil_photo_back'] &&
                !$this->updateCivilId('back')
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * update candidate experiences
     * @param $experiences
     * @return array|bool
     */
    public function updateExperiences($experiences)
    {
        CandidateExperience::deleteAll([
            'candidate_id' => $this->candidate_id
        ]);

        if (empty($experiences))
        {
            return null;
        }

        $experiences = explode(',', $experiences);

        if (count($experiences) == 0)
        {
            return null;
        }

        foreach ($experiences as $experience) {

            if (empty($experience)) {
                continue;
            }

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

        return true;
    }

    /**
     * update candidate tags
     * @param $tags
     * @return array|bool
     */
    public function updateTags($tags)
    {
        CandidateTag::deleteAll([
            'candidate_id' => $this->candidate_id
        ]);

        $tags_array = $tags? explode(',', $tags): [];

        if (empty($tags) || count($tags_array) == 0)
        {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Tags Required")
            ];
        }

        foreach ($tags_array as $tag)
        {
            if (empty($tag)) {
                continue;
            }

            $model = new CandidateTag;
            $model->candidate_id = $this->candidate_id;
            $model->tag = $tag;

            if(!$model->save()) {
                return [
                    "operation" => "error",
                    "message" => $model->getErrors()
                ];
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
        CandidateSkill::deleteAll([
            'candidate_id' => $this->candidate_id
        ]);

        $skills_array = $skills? explode(',', $skills): [];

        if (empty($skills) || count($skills_array) == 0)
        {
            return [
                "operation" => "error",
                "message" => Yii::t('candidate',"Skills Required")
            ];
        }

        foreach ($skills_array as $skill)
        {
            if (empty($skill)) {
                continue;
            }

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

        return true;
    }

    /**
     * @return bool
     */
    public function updateResume() {

        if (!empty($this->oldAttributes['candidate_resume'])) {
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
     * @param $candidates
     * @return int
     */
    public static function calculateRemainingPaymentTransferTotal($candidates) {
        $totalAmount = 0;
        if (count($candidates)>0) {
            foreach ($candidates as $candidateTransfer) {
                $totalAmount += $candidateTransfer->candidate_total;
                //$candidateTransfer['bonus'] - $candidateTransfer['bonus_commission'] +
                //    ($candidateTransfer['hours'] * $candidateTransfer['candidate_hourly_rate']);
            }
        }
        return $totalAmount;
    }

    /**
     * @param $candidate_name
     * @return \common\models\query\CandidateQuery
     */
    public static function getAssignedIdleCandidate($candidate_name = null) {

        $query = Candidate::find()
            ->filterAssigned()
            ->getTwoMonthBeforeTransfers()
            ->notDeleted();

        if ($candidate_name) {
            $query->filterName($candidate_name);
        }

        return $query;
    }

    /**
     * Return a List of Candidate assigned to store and bank is null
     * or bank is null and transfer is pending.
     * @param null $candidate_name
     * @return \common\models\query\CandidateQuery
     */
    public static function withoutBankInfoOrWithPayment($candidate_name = null)
    {
        $query = Candidate::find()
            ->joinWith('transferCandidate')
            ->joinWith('transfers')
            ->andWhere('{{%candidate}}.store_id > 0 && {{%candidate}}.bank_id IS NULL')
            ->orWhere('{{%transfer_candidate}}.deleted = 0 && {{%transfer_candidate}}.paid = 0 && {{%candidate}}.bank_id IS NULL && {{%transfer}}.transfer_status in ('.Transfer::STATUS_TRANSFER_COMPLETE.','.Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS.')')
            ->groupBy('{{%candidate}}.candidate_id');

        if ($candidate_name) {
            $query->filterName($candidate_name);
        }

        $query->notDeleted();

        return $query;
    }

    /**
     * @return \common\models\query\CandidateQuery
     */
    public static function incompleteAssignedToWork() {
        return self::find()
            ->filterAssigned()
            ->incompletedProfile()
            ->notDeleted();
    }

    /**
     * @return \common\models\query\CandidateQuery
     */
    public static function profileApprovalRequire() {
        return self::find()
            ->byApprovalStatus(0)
            ->completedProfileWithoutApproval()
            ->notDeleted();
    }

    /**
     * @return \common\models\query\CandidateQuery
     */
    public static function assignedExpiredCivilID() {
        return self::find()
            ->civilIdExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted();
    }

    /**
     * @return \common\models\query\CandidateQuery
     */
    public static function totalExpiredCards() {

        return self::find()
            ->idExpired()
            ->filterAssigned() // only candidate with assigned work
            ->notDeleted();
    }

    public function getInvited() {
        return $this->getInvitations()->count();
    }

    public function getInvitationAccepted() {
        return $this->getInvitations()->andWhere(['invitation_status' => Invitation::STATUS_ACCEPTED])->count();
    }

    public function getInvitationRejected() {
        return $this->getInvitations()->andWhere(['invitation_status' => Invitation::STATUS_REJECTED])->count();
    }

    public function getSuggested() {
        return $this->getSuggestion()->count();
    }

    public function getSuggestionAccepted() {
        return $this->getSuggestion()->andWhere(['suggestion_status' => Suggestion::TYPE_ACCEPTED])->count();
    }

    public function getSuggestionRejected() {
        return $this->getSuggestion()->andWhere(['suggestion_status' => Suggestion::TYPE_REJECTED])->count();
    }
}
