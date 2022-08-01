<?php

use yii\db\Migration;

/**
 * Class m220729_055851_work_history_changes
 */
class m220729_055851_work_history_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn (
            'candidate_work_history',
            'staff_id',
            $this->integer(1)->null()->after('parent_company_id')
        );

        $this->createIndex(
            'idx-candidate_work_history-staff_id',
            'candidate_work_history',
            'staff_id'
        );

        $this->addForeignKey(
            'fk-candidate_work_history-staff_id',
            'candidate_work_history',
            'staff_id',
            'staff',
            'staff_id',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220729_055851_work_history_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220729_055851_work_history_changes cannot be reverted.\n";

        return false;
    }
    */
}
