<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[Store]].
 *
 */
class StoreQuery extends \yii\db\ActiveQuery
{
    /**
     * @param $companyId
     * @return $this
     */
    public function filterCompany($companyId)
	{
		return $this->andWhere(['{{%store}}.company_id' => $companyId]);
	}

    /**
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['deleted'=>0]);
    }

    public function getCandidates() {
        return [];
    }
}