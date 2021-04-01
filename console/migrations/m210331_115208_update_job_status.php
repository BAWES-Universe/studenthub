<?php

use yii\db\Migration;

/**
 * Class m210331_115208_update_job_status
 */
class m210331_115208_update_job_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210331_115208_update_job_status cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210331_115208_update_job_status cannot be reverted.\n";

        return false;
    }
    */
}
