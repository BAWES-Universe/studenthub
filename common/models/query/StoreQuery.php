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
     * @inheritdoc
     * @return Store[]|array
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%store}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Store|array|null
     */
    public function one($db = null)
    {
        //$this->andWhere(['{{%store}}.deleted'=>0]);
        return parent::one($db);
    }

    /**
     * @param $companyId
     * @return $this
     */
    public function filterCompany($companyId)
    {
        return $this->andWhere(['{{%store}}.company_id' => $companyId]);
    }

    /**
     * @param $store_id
     * @return StoreQuery
     */
    public function filterByStoreId($store_id)
    {
        return $this->andWhere(['{{%store}}.store_id' => $store_id]);
    }
}
