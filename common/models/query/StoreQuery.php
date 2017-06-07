<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[Store]].
 *
 */
class StoreQuery extends \yii\db\ActiveQuery
{
	public function filterCompany($companyId) 
	{
		return $this->where(['{{%store}}.company_id' => $companyId]);
	}
}