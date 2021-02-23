<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\CandidateIdCard;
use common\models\Store;

/**
 * This is the ActiveQuery class for [[Candidate]].
 */
class CandidateQuery extends \yii\db\ActiveQuery 
{
    /**
     * @inheritdoc
     * @return CandidateWorkHistory[]|array
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%candidate}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateWorkHistory|array|null
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%candidate}}.deleted'=>0]);
        return parent::one($db);
    }

    /**
     * @param $company
     * @return $this
     */
    public function filterCompany($company)
    {
        // create company_id array from all sub companies and self

        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        // create store_id array 

        $stores = Store::find()
            ->andWhere(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, '{{%candidate}}.store_id', 'store_id');

        // return candidate list for given company 
        
        return $this->andWhere(['in', '{{%candidate}}.store_id', $store_ids]);
    }

    /**
     * @param $candidate_name
     * @return $this
     */
    public function filterName($candidate_name)
    {
        return $this->andWhere(['like', '{{%candidate}}.candidate_name', $candidate_name]);
    }

    /**
     * @param $candidate_email
     * @return $this
     */
    public function filterEmail($candidate_email)
    {
        return $this->andWhere(['like', '{{%candidate}}.candidate_email', $candidate_email]);
    }

    /**
     * @param $candidate_phone
     * @return $this
     */
    public function filterPhone($candidate_phone)
    {
        return $this->andWhere(['like', '{{%candidate}}.candidate_phone', $candidate_phone]);
    }

    /**
     * @return $this
     */
    public function filterAssigned()
    {
        return $this->andWhere('{{%candidate}}.store_id > 0');
    }

    /**
     * @return $this
     */
    public function filterNotAssigned()
    {
        return $this->andWhere('{{%candidate}}.store_id IS NULL or {{%candidate}}.store_id = 0');
    }

    /**
     * @param $store_id
     * @return $this
     */
    public function filterStore($store_id)
    {
        return $this->andWhere(['{{%candidate}}.store_id' => $store_id]);
    }

    /**
     * @param $country_id
     * @return $this
     */
    public function filterCountry($country_id)
    {
        return $this->andWhere(['{{%candidate}}.country_id' => $country_id]);
    }

    /**
     * @param $university_id
     * @return $this
     */
    public function filterUniversity($university_id)
    {
        return $this->andWhere(['{{%candidate}}.university_id' => $university_id]);
    }

    /**
     * @return $this
     */
    public function civilIdExpired()
    {
        return $this->andWhere('DATE(candidate_civil_expiry_date) < DATE(NOW())');
    }

    /**
     * @return $this
     */
    public function idExpired()
    {
        return $this
            ->joinWith('candidateIdCard')
            ->andWhere('DATE(expiry_date) < DATE(NOW())');
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function idNeedGenerated()
    {
        return $this->andWhere('candidate_id NOT IN (select candidate_id from candidate_id_card)');
    }

    /**
     * @return int|string
     */
    public function totalAssigned()
    {
    	return $this->andWhere('{{%candidate}}.store_id > 0');
	}

    /**
     * @return int|string
     */
    public function totalUnassigned()
    {
        return $this->andWhere('{{%candidate}}.store_id IS NULL OR {{%candidate}}.store_id = 0');
    }

    /**
     * @return int|string
     */
    public function withBankInfo()
    {
        return $this->andWhere('{{%candidate}}.bank_id IS NULL');
    }

    /**
     * @param $status
     * @return $this
     */
    public function byApprovalStatus($status = 0) {
        return $this->andWhere(['{{%candidate}}.approved' => $status]);
    }

    /**
     * @param $status
     * @return $this
     */
    public function orderByStatus() {
        return $this->addOrderBy('{{%candidate}}.approved DESC');
    }

    /**
     * @param $status
     * @return $this
     */
    public function verifiedProfile() {
        return $this->andWhere(['{{%candidate}}.candidate_email_verification' => 1]);
    }

    /**
     * @param $id
     * @return CandidateQuery
     */
    public function filterById($id)
    {
        return $this->andWhere(['{{%candidate}}.candidate_id'=>$id]);
    }

    /**
     * @return CandidateQuery
     */
    public function notDeleted() {
        return $this->andWhere(['{{%candidate}}.deleted'=>0]);
    }

    public function incompletedProfile() {
        return $this->andWhere('{{%candidate}}.candidate_uid IS NULL OR
          {{%university}}.university_id IS NULL OR {{%country}}.country_id IS NULL OR
          {{%candidate}}.candidate_name IS NULL OR {{%candidate}}.candidate_name_ar IS NULL OR
          {{%candidate}}.candidate_gender IS NULL OR {{%candidate}}.candidate_objective IS NULL OR
          {{%candidate}}.candidate_personal_photo IS NULL OR {{%candidate}}.candidate_email IS NULL OR
          {{%candidate}}.candidate_phone IS NULL OR {{%candidate}}.candidate_birth_date IS NULL OR
          {{%candidate}}.candidate_civil_id IS NULL OR {{%candidate}}.candidate_civil_expiry_date IS NULL OR
          {{%candidate}}.candidate_civil_photo_front IS NULL OR {{%candidate}}.candidate_civil_photo_back IS NULL OR
          {{%candidate}}.candidate_driving_license IS NULL OR {{%candidate_experience}}.candidate_experience_id IS NULL OR 
          {{%candidate_skill}}.candidate_skill_id IS NULL')
            ->groupBy('{{%candidate}}.candidate_id')
            ->joinWith([
                'candidateExperiences',
                'candidateSkills',
                'country',
                'university'
            ]);
//            ->asArray();
    }

    public function completedProfileWithoutApproval() {
        return $this->andWhere('{{%candidate}}.candidate_uid IS NOT NULL')
            ->andWhere('university.university_id IS NOT NULL AND country.country_id IS NOT NULL AND 
                {{%candidate}}.candidate_name IS NOT NULL AND {{%candidate}}.candidate_name_ar IS NOT NULL AND 
                {{%candidate}}.candidate_gender IS NOT NULL AND {{%candidate}}.candidate_objective IS NOT NULL AND
                {{%candidate}}.candidate_personal_photo IS NOT NULL AND {{%candidate}}.candidate_email IS NOT NULL AND 
                {{%candidate}}.candidate_phone IS NOT NULL AND {{%candidate}}.candidate_birth_date IS NOT NULL AND 
                {{%candidate}}.candidate_civil_id IS NOT NULL AND {{%candidate}}.candidate_civil_expiry_date IS NOT NULL AND 
                {{%candidate}}.candidate_civil_photo_front IS NOT NULL AND {{%candidate}}.candidate_civil_photo_back IS NOT NULL AND 
                {{%candidate}}.candidate_driving_license IS NOT NULL AND candidate_experience.candidate_experience_id IS NOT NULL AND
                candidate_skill.candidate_skill_id IS NOT NULL')
            ->groupBy('{{%candidate}}.candidate_id')
            ->innerJoinWith([
                'candidateExperiences',
                'candidateSkills',
                'country',
                'university'
            ]);
    }

    /**
     * candidates that are assigned to work but
     * have no TransferCandidate records in past 2 months
     *
     * used two conditions. one is for without transfer in 2 months
     * second one check if user's started the job passed two months
     */
    public function getTwoMonthBeforeTransfers() {
        return $this->andWhere('candidate_id NOT IN (SELECT candidate_id FROM `transfer_candidate` where (`transfer_candidate`.tc_created_at > DATE_SUB(NOW(),INTERVAL 2 MONTH)) group by candidate_id)')
        ->andWhere('candidate_id NOT IN (SELECT candidate_id FROM `candidate_work_history` where (`candidate_work_history`.`start_date` > DATE_SUB(NOW(),INTERVAL 2 MONTH)) and end_date IS NUll group by candidate_id)');
        //last 2 MONTH
    }

    /**
     * @param $status
     * @return $this
     */
    public function candidateMomKuwaitiFieldIsNull() {
        return $this->andWhere('{{%candidate}}.`candidate_mom_kuwaiti` IS NULL');
    }
}
