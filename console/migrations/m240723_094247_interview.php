<?php

use yii\db\Migration;

/**
 * Class m240723_094247_interview
 */
class m240723_094247_interview extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%interview_evaluation}}', "request_uuid", $this->char(60)->null());
        $this->alterColumn('{{%interview_evaluation}}', '{{%company_id}}', $this->integer(11)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240723_094247_interview cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240723_094247_interview cannot be reverted.\n";

        return false;
    }
    */
}
