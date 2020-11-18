<?php

use yii\db\Migration;

/**
 * Class m201106_074724_candidate_mom_kuwaiti_field
 */
class m201106_074724_candidate_mom_kuwaiti_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate','candidate_mom_kuwaiti',$this->boolean()->after('approved')->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201106_074724_candidate_mom_kuwaiti_field cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201106_074724_candidate_mom_kuwaiti_field cannot be reverted.\n";

        return false;
    }
    */
}
