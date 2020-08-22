<?php

use yii\db\Migration;

/**
 * Class m200821_123604_last_follow
 */
class m200821_123604_last_follow extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%company}}', 'company_last_followup_datetime', $this->datetime()->defaultValue(new \yii\db\Expression('NOW()'))->after('company_followup'));
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
        echo "m200821_123604_last_follow cannot be reverted.\n";

        return false;
    }
    */
}
