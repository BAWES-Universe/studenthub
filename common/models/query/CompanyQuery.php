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
        return $this->filterWhere([
            'AND',
            'company_followup' => true,
            new \yii\db\Expression('DATE_ADD(company_last_followup_datetime,INTERVAL company_followup_interval_weeks WEEK) <= NOW()')
        ]);
    }
    
    /**
     * @return $this
     */
    public function filterParent() {
        return $this->andWhere(['{{%company}}.parent_company_id' => null]);
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
        return $this->andWhere(['like', '{{%company}}.company_name', $name]);
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
     * @param $id
     * @return $this
     */
    public function filterActive() {
        return $this->andWhere(['or',
            ['>','total_candidate',0],
            ['>','no_of_active_requests',0],
            ['>','is_request_updates_in_30_days',0],
        ]);
    }

    /**
     * @param $id
     * @return $this
     * store_total_candidates
     */
    public function filterInActive() {
        return $this->andWhere(['AND',
            ['total_candidate'=>0],
            ['no_of_active_requests'=>0],
            ['is_request_updates_in_30_days'=>0],
        ]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByActive40DaysPassedWithoutPayment() {
        return $this->andWhere('{{%company}}.company_id NOT IN (SELECT company_id FROM `transfer` where transfer_status in (1,3,4) and transfer_created_at >= DATE_SUB(NOW(),INTERVAL 40 DAY))')
        ->andWhere('(company.`total_candidate` > 0 OR company.is_request_updates_in_30_days > 0 OR company.no_of_active_requests > 0)');
    }
}
