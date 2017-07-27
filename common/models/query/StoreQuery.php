<?php
namespace common\models\query;

use Yii;
use yii\db\ActiveQuery;
/**
 * This is the ActiveQuery class for [[Store]].
 *
 */
class StoreQuery extends ActiveQuery
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
        return $this->andWhere(['{{%store}}.deleted'=>0]);
    }
}