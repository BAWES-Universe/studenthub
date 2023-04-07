<?php

use yii\db\Migration;

/**
 * Class m230406_110939_candidate_profile_url
 */
class m230406_110939_candidate_profile_url extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate','profile_url',$this->string(225)->after('candidate_pending_profile'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('candidate','profile_url');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230406_110939_candidate_profile_url cannot be reverted.\n";

        return false;
    }
    */
}
