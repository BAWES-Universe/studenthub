<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;


/**
 * This is the ActiveQuery class for [[Inspector]].
 *
 */
class InspectorQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%inspector}}.inspector_deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%inspector}}.inspector_deleted' => 0]);
        return parent::one($db);
    }
}
