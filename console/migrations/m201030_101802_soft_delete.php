<?php

use yii\db\Migration;

/**
 * Class m201030_101802_soft_delete
 */
class m201030_101802_soft_delete extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate_id_card', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('expiry_date'));
        $this->addColumn('candidate_skill', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('skill'));
        $this->addColumn('candidate_experience', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('experience'));
        $this->addColumn('transfer_candidate', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('transfer_cost'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201030_101802_soft_delete cannot be reverted.\n";

        return false;
    }
    */
}
