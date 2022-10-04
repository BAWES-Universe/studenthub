<?php

use yii\db\Migration;

/**
 * Class m221004_090413_staff_job_title
 */
class m221004_090413_staff_job_title extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('staff', 'staff_job_title', $this->string(100)->after('staff_name'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221004_090413_staff_job_title cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221004_090413_staff_job_title cannot be reverted.\n";

        return false;
    }
    */
}
