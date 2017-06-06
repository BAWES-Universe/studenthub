<?php 

namespace common\models\query;

use Yii;

/**
 * This is the ActiveQuery class for [[Company]].
 *
 */
class CompanyQuery extends \yii\db\ActiveQuery
{
	public function filterParent()
	{
		return $this->where(['parent_company_id' => null]);		
	}
}
 
