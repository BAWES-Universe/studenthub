<?php

use yii\db\Migration;

/**
 * Class m220313_081902_delete_candidate_id_card_entires
 */
class m220313_081902_delete_candidate_id_card_entires extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
      Yii::$app->db->createCommand("delete from `candidate_id_card`  where DATE(created_at) >= '2022-03-06' AND deleted = '1'")->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        return false;
    }

}
