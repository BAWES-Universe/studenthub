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
        $this->andWhere(['{{%bank}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%bank}}.deleted'=>0]);
        return parent::one($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(['{{%bank}}.deleted' => 0]);
        return parent::count($q);
    }
}
	