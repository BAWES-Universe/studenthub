<?php

namespace common\models\query;

use common\models\Request;
use common\models\Candidate;
use common\models\Transfer;
use Yii;
use yii\db\Expression;
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
     * @return Candidate[]|array
     */
    public function all($db = null)
    {
     //   $this->andWhere(['{{%candidate}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Candidate|array|null
     */
    public function one($db = null)
    {
        //$this->andWhere(['{{%candidate}}.deleted'=>0]);
        return parent::one($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function count($q = '*', $db = null)
    {
      //  $this->andWhere(['{{%candidate}}.deleted' => 0]);
        return parent::count($q);
    }

    /**
     * @param $company
     * @return $this
     */
    public function filterCompany($company)
    {
        if(!$company)
            return $this;

        // create company_id array from all sub companies and self
        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        // create store_id array 
        $stores = Store::find()
            ->andWhere(['in', 'store.company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

        return $this->andWhere(['in', '{{%candidate}}.store_id', $store_ids]);
    }

    /**
     * @param $updatedAfter
     * @return $this
     */
    public function filterUpdatedAfter($updatedAfter)
    {
        return $this->andWhere(new Expression("DATE(candidate_updated_at) > DATE('".$updatedAfter."') and DATE(candidate_created_at) != DATE(candidate_updated_at)"));
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
     * @param $country
     * @return CandidateQuery
     */
    public function filterCountryName($country) {
        return $this
            ->joinWith('country')
            ->andWhere([
                "OR",
                ['{{%country}}.country_name_en' => $country],
                ['{{%country}}.country_name_ar' => $country]
            ]);
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
     * @param $university_id
     * @return $this
     */
    public function filterCivil($civil_id)
    {
        return $this->andWhere(['{{%candidate}}.candidate_civil_id' => $civil_id]);
    }

    /**
     * @return $this
     */
    public function civilIdExpired()
    {
        return $this->andWhere('DATE(candidate_civil_expiry_date) < DATE(NOW())');
    }

    /**
     * @return CandidateQuery
     */
    public function activeCivilId()
    {
        return $this->andWhere('DATE({{%candidate}}.candidate_civil_expiry_date) >= DATE(NOW())');
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
     * @return $this
     */
    public function filterByJoiningDate($startDate = null, $endDate = null, $companyID = null)
    {
        $this->joinWith('workHistory');

        if ($startDate) {
            $startDate = date('Y-m-d', strtotime($startDate));
            $this->andWhere("DATE(candidate_work_history.start_date) >= '$startDate'");
        }
        if ($endDate) {
            $endDate = date('Y-m-d', strtotime($endDate));
            $this->andWhere("DATE(candidate_work_history.start_date) <= '$endDate'");
        }

        if ($companyID) {
            $this->andWhere(["`candidate_work_history`.`parent_company_id`" => $companyID]);
        }

        return $this;
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function idNeedGenerated()
    {
        return $this->joinWith(['candidateIdCards'])
            ->andWhere(new Expression("candidate_id_card.id IS NULL"));
        //andWhere('candidate_id NOT IN (select candidate_id from candidate_id_card where deleted = 0 )');
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
     * @param $id
     * @return CandidateQuery
     */
    public function filterByRequestRequirement($match_request_id) {

        $request = Request::findOne($match_request_id);

        $requestSkills = ArrayHelper::getColumn($request->getRequestSkills()->all(), 'skill');

        //matching skills or experience

        $this->joinWith(['candidateSkills', 'candidateExperiences'])
            ->andWhere([
                "OR",
                ["IN", 'candidate_skill.skill', $requestSkills],
                ["IN", 'candidate_experience.experience', $request->request_position_title]
            ]);

        if($request->gender) {
            $this->andWhere(['candidate_gender' => $request->gender]);
        }

        if($request->nationality_id) {
            $this->andWhere(['country_id' => $request->nationality_id]);
        }

        return $this->andWhere(new Expression("candidate.store_id IS NULL"));
    }

    /**
     * @return CandidateQuery
     */
    public function notDeleted() {
        return $this->andWhere(['{{%candidate}}.deleted'=>0]);
    }

    public function incompletedProfile() {
        return $this->andWhere(['{{%candidate}}.is_incomplete_profile' => 1]);

        /*return $this->andWhere('{{%candidate}}.candidate_pending_profile IS NULL OR
            {{%candidate}}.candidate_pending_profile=""');*/

        /*return $this->andWhere('{{%candidate}}.candidate_uid IS NULL OR
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
//            ->asArray();*/
    }

    public function completedProfileWithoutApproval() {
        return $this->andWhere(['{{%candidate}}.is_incomplete_profile' => 0, 'approved' => 0]);

        /*return $this->andWhere('{{%candidate}}.candidate_uid IS NOT NULL')
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
            ]);*/
    }

    /**
     * candidates that are assigned to work but
     * have no TransferCandidate records in past 2 months
     *
     * used two conditions. one is for without transfer in 2 months
     * second one check if user's started the job passed two months
     */
    public function getTwoMonthBeforeTransfers() {
        $date = date('Y-m-d', strtotime('-2 month'));

        //return $this->andWhere('candidate_id NOT IN (SELECT candidate_id FROM `candidate_work_history` where DATE(`candidate_work_history`.`start_date`) > DATE("'.$date.'") and end_date IS NUll group by candidate_id)')
        //    ->andWhere('candidate_id NOT IN (SELECT candidate_id FROM `transfer_candidate` where DATE(`transfer_candidate`.tc_created_at) > DATE("'.$date.'") group by candidate_id)');

        //->andWhere(new Expression("candidate.store_id IS NOT NULL")) //candidate assigned to store

        return $this->joinWith(['candidateWorkHistories', 'transferCandidates'])
            //no transfer in last 2 month
            // always true -> DATE(`transfer_candidate`.tc_created_at) < CURRENT_DATE() AND
            ->andWhere(new Expression("DATE(`transfer_candidate`.tc_created_at) > DATE('".$date."') AND 
                transfer_candidate.tc_id IS NULL"))
            //was assigned before 2 month
            ->andWhere(new Expression("DATE(`candidate_work_history`.`start_date`) < DATE('".$date."')"));
    }

    /**
     * @param $status
     * @return $this
     */
    public function candidateMomKuwaitiFieldIsNull() {
        return $this->andWhere('{{%candidate}}.`candidate_mom_kuwaiti` IS NULL');
    }

    public function withoutBankInfo() {
        return $this
            ->joinWith('transferCandidate')
            ->joinWith('transfers')
            ->andWhere('{{%candidate}}.store_id > 0 && {{%candidate}}.bank_id IS NULL')
            ->orWhere('{{%transfer_candidate}}.deleted = 0 && {{%transfer_candidate}}.paid = 0 && {{%candidate}}.bank_id IS NULL && {{%transfer}}.transfer_status in ('.Transfer::STATUS_TRANSFER_COMPLETE.','.Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS.')')
            ->groupBy('{{%candidate}}.candidate_id')
            ->notDeleted();
    }

    public function getSqlQuery() {
        return $this->createCommand()->getRawSql();
    }
}
