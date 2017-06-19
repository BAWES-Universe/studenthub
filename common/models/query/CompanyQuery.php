<?php 

namespace common\models\query;

use Yii;

/**
 * This is the ActiveQuery class for [[Company]].
 *
 */
class CompanyQuery extends \yii\db\ActiveQuery
{
    /**
     * @return $this
     */
    public function filterParent()
	{
		return $this->andWhere(['parent_company_id' => null]);		
	}

    /**
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['deleted'=>0]);
    }

    public function childCompany($id)
    {
        return $this->andWhere(['parent_company_id' => $id]);
    }
}
 
