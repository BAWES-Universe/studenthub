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
        $q = 'SELECT company_id FROM `company` WHERE  (`parent_company_id` IS NULL) AND ';
        $q .= '((( select count(*) from `request` where `request`.`company_id` = `company`.`company_id` and `request`.`request_status` = "active" OR `request`.`request_updated_datetime` > DATE_SUB(NOW(),INTERVAL 30 DAY)) > 0) OR ';
        $q .= '(( select sum(`store`.`store_total_candidates`) as total from `store` where `store`.`company_id` = `company`.`company_id` AND (`store`.`deleted`=0)) > 0))';
        $q .= ' union ';
        $q .= 'SELECT parent_company_id FROM `company` WHERE  (`parent_company_id` IS NOT NULL) ';
        $q .= 'AND (( select count(*) from `request` where `request`.`company_id` = `company`.`company_id` and `request`.`request_status` = "active" OR `request`.`request_updated_datetime` > DATE_SUB(NOW(),INTERVAL 30 DAY)) > 0) ';
        $q .= 'AND  (( select sum(`store`.`store_total_candidates`) as total from `store` where `store`.`company_id` = `company`.`company_id` AND (`store`.`deleted`=0)) > 0) group by parent_company_id';
        return $this->andWhere('{{%company}}.company_id IN ('.$q.')');
    }

    /**
     * @param $id
     * @return $this
     * store_total_candidates
     */
    public function filterInActive() {
        $q = 'SELECT company_id FROM `company` WHERE  (`parent_company_id` IS NULL) AND ';
        $q .= '(( select count(*) from `request` where `request`.`company_id` = `company`.`company_id` and `request`.`request_status` != "active" and `request`.`request_updated_datetime` > DATE_SUB(NOW(),INTERVAL 30 DAY)) = 0) AND ';
        $q .= '(( select sum(`store`.`store_total_candidates`) as total from `store` where `store`.`company_id` = `company`.`company_id` AND (`store`.`deleted`=0)) = 0) AND ';
        $q .= '(SELECT count(*) FROM `company` as `sub` WHERE  (`sub`.`parent_company_id` IS NULL) AND `sub`.`parent_company_id`= `company`.`company_id` AND ';
        $q .= '((( select count(*) from `request` where `request`.`company_id` = `sub`.`company_id` and `request`.`request_status` = "active" OR `request`.`request_updated_datetime` > DATE_SUB(NOW(),INTERVAL 30 DAY)) > 0) OR ';
        $q .= '(( select sum(`store`.`store_total_candidates`) as total from `store` where `store`.`company_id` = `sub`.`company_id` AND (`store`.`deleted`=0)) > 0))) = 0 ';
        return $this->andWhere('{{%company}}.company_id IN ('.$q.')');
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterByActive40DaysPassedWithoutPayment() {
        return $this->andWhere('{{%company}}.company_id NOT IN (SELECT company_id FROM `transfer` where transfer_status in (1,3,4) and transfer_created_at >= DATE_SUB(NOW(),INTERVAL 40 DAY))')
        ->andWhere(['{{%company}}.company_status' => Company::STATUS_ACTIVE]);
    }
}
