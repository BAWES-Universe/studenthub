<?php

namespace common\models\query;

use common\models\Company;
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
        return $this->andWhere(['parent_company_id' => null]);        
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
}
