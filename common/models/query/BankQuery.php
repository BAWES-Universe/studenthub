<?php

namespace common\models\query;

/**
 * This is the ActiveQuery class for [[Bank]].
 *
 */
class BankQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['deleted'=>0]);
    }
}
	