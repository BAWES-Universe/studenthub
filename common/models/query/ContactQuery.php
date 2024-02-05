<?php

namespace common\models\query;

/**
 * This is the ActiveQuery class for [[Contact]].
 *
 */
class ContactQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%contact}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%contact}}.deleted' => 0]);
        return parent::one($db);
    }
}