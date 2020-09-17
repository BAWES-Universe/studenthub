<?php

namespace common\models\query;

use common\models\Company;
use Yii;

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
