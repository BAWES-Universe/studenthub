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
        return $this->andWhere(['{{%company}}.deleted'=>0]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function childCompany($id)
    {
        return $this->andWhere(['parent_company_id' => $id]);
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterCompany($id)
    {
        return $this->andWhere(['{{%company}}.company_id'=>$id]);
    }
}
 
