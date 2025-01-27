<?php

namespace common\models\query;

use yii\db\Expression;


/**
 * This is the ActiveQuery class for [[Job]].
 *
 */
class JobQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(new Expression('{{%job}}.deleted_at IS NULL'));
        return parent::count($q);
    }

    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(new Expression('{{%job}}.deleted_at IS NULL'));
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(new Expression('{{%job}}.deleted_at IS NULL'));
        return parent::one($db);
    }
}
