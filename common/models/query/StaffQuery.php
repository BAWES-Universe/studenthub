<?php

namespace common\models\query;

use Yii;
use yii\db\ActiveQuery;
/**
 * This is the ActiveQuery class for [[Staff]].
 *
 */
class StaffQuery extends ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%staff}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%staff}}.deleted'=>0]);
        return parent::one($db);
    }

    public function withoutCurrentUser() {
        return $this->andWhere(['!=','staff_id', Yii::$app->user->id]);
    }

    public function active() {
        return $this->andWhere(['{{%staff}}.deleted'=>0]);
    }
}
