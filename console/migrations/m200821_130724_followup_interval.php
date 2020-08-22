<?php

use yii\db\Migration;

/**
 * Class m200821_130724_followup_interval
 */
class m200821_130724_followup_interval extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%company}}', 
            'company_followup_interval_weeks', 
            $this->smallInteger()->defaultValue(1)->after('company_followup')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200821_130724_followup_interval cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200821_130724_followup_interval cannot be reverted.\n";

        return false;
    }
    */
}
