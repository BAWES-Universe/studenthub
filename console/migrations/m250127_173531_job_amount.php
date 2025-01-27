<?php

use yii\db\Migration;

/**
 * Class m250127_173531_job_amount
 */
class m250127_173531_job_amount extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn("job", "compensation_amount", $this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250127_173531_job_amount cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250127_173531_job_amount cannot be reverted.\n";

        return false;
    }
    */
}
