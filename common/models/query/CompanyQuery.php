<?php

namespace common\models\query;

use common\models\Company;
use common\models\Transfer;
use Yii;
use yii\db\Expression;

/**
 * This is the ActiveQuery class for [[Company]].
 *
 */
class CompanyQuery extends \yii\db\ActiveQuery {

    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%company}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%company}}.deleted' => 0]);
        return parent::one($db);
    }

    /**
     * company need followups
     * @return CompanyQuery
     */
    public function followups() {
        return $this->andWhere(['company_followup' => 1])
            ->andWhere(
                new \yii\db\Expression(
                    'DATE_ADD(company_last_followup_datetime,INTERVAL company_followup_interval_weeks WEEK) <= NOW()'
                )
            );
    }

    /**
     * @return $this
     */
    public function filterChild() {
        return $this->andWhere(new Expression('{{%company}}.parent_company_id IS NOT NULL'));
    }

    /**
     * @return $this
     */
    public function filterParent() {
        return $this->andWhere(new Expression('{{%company}}.parent_company_id IS NULL'));
    }

    /**
     * filter companies without parent + child
     * @return CompanyQuery
     */
    public function filterWithoutChild()
    {
        $subQuery = Company::find()
            ->select('parent_company_id')
            ->andWhere (new Expression('parent_company_id IS NOT NULL'));

        return $this->andWhere (['not in', 'company_id', $subQuery])//not parent company
            ->andWhere (new Expression('parent_company_id IS NULL'));//not having parent
    }

    /**
     * @param $id
     * @return $this
     */
    public function childCompany($id) {
        return $this->andWhere(['{{%company}}.parent_company_id' => $id]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterCompany($id) {
        return $this->andWhere(['{{%company}}.company_id' => $id]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByName($name) {
        return
            $this->andWhere([
                'or',
                ['like', '{{%company}}.company_name', $name],
                ['like', '{{%company}}.company_common_name_ar', $name],
                ['like', '{{%company}}.company_common_name_en', $name]
            ]);

    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByNameAr($name) {
        return $this->andWhere(['like', '{{%company}}.company_common_name_ar', $name]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByNameEn($name) {
        return $this->andWhere(['like', '{{%company}}.company_common_name_en', $name]);
    }

 /**
     * @param $staffID
     * @return $this
     */
    public function filterByStaff($staffID) {
        return $this->andWhere(['{{%company}}.staff_id' => $staffID]);
    }

    /**
     * filter by ability to invite candidates
     * @param $approved_to_hire
     * @return CompanyQuery
     */
    public function filterByApprovedToHire($approved_to_hire) {
        return $this->andWhere(['company_approved_to_hire' => $approved_to_hire]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterActive() {
        return $this->andWhere(['or',
            ['>','{{%company}}.total_candidate',0],
            ['>','{{%company}}.no_of_active_requests',0],
            ['>','{{%company}}.is_request_updates_in_30_days',0],
        ]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterActiveWithOnlyStaff() {
        return $this->andWhere(['>','{{%company}}.total_candidate',0]);
    }

    public function filterUnderReview() {
        return $this->andWhere(['company_status_override' => Company::STATUS_UNDER_REVIEW]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterInActive() {
        return $this->andWhere([
            'AND',
            ['{{%company}}.total_candidate'=>0],
            ['{{%company}}.no_of_active_requests'=>0],
            ['{{%company}}.is_request_updates_in_30_days'=>0],
        ]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByActive40DaysPassedWithoutPayment() {
        $q = '{{%company}}.company_id NOT IN (SELECT company_id FROM `transfer` where ';
        $q .= 'transfer_status in (1,3,4) and DATE(transfer_created_at) > DATE_SUB(NOW(),INTERVAL 40 DAY))';
        $q .= ' AND ({{%company}}.`total_candidate` > 0)';
        $q .= ' AND {{%company}}.company_id IN (SELECT parent_company_id FROM `candidate_work_history` where DATE(start_date) < DATE_SUB(NOW(),INTERVAL 30 DAY) group by parent_company_id)';
        return
            $this
                ->andWhere($q);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByActive40DaysPassedWithoutRequest() {
        $q = '{{%company}}.company_id NOT IN (';
        $q .= 'SELECT IFNULL(`company`.`parent_company_id`,`company`.`company_id`) as company_id FROM `request` ';
        $q .= 'left join company on `request`.`company_id` = `company`.`company_id` where ';
        $q .= '`request`.`request_created_datetime` > DATE_SUB(NOW(),INTERVAL 40 DAY) and ';
        $q .= ' `company`.`company_created_at` < DATE_SUB(NOW(),INTERVAL 40 DAY) GROUP BY `company`.`company_id`)';
        return
            $this
                ->andWhere($q);
    }

    public function notDeleted() {
        return $this->andWhere(['{{%company}}.deleted' => 0]);
    }
}
