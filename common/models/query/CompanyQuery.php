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
        return $this->andWhere(['parent_company_id' => null]);        
    }

    /**
     * @return $this
     */
    public function notDeleted() {
        return $this->andWhere(['{{%company}}.deleted' => 0]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function childCompany($id) {
        return $this->andWhere(['parent_company_id' => $id]);
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
        return $this->andWhere(['{{%company}}.company_status' => Company::STATUS_ACTIVE]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterInActive() {
        return $this->andWhere(['{{%company}}.company_status' => Company::STATUS_INACTIVE]);
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
