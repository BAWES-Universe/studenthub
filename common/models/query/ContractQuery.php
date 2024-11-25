<?php

namespace common\models\query;

class ContractQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%contract}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param $q
     * @param $db
     * @return bool|int|string|null
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(['{{%contract}}.deleted' => 0]);
        return parent::count($q, $db);
    }
}