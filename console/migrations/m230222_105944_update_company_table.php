<?php

use yii\db\Migration;

/**
 * Class m230222_105944_update_company_table
 */
class m230222_105944_update_company_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('company','staff_id',$this->integer()->after('parent_company_id')->null()->comment('managed by'));

            // creates index for column `staff_id`
        $this->createIndex(
            'idx-company-staff_id',
            'company',
            'staff_id'
        );
//        // add foreign key for table `staff`
        $this->addForeignKey(
            'fk-company-staff_id',
            'company',
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
        $this->dropForeignKey('fk-company-staff_id','company');
        $this->dropColumn('company','staff_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230222_105944_update_company_table cannot be reverted.\n";

        return false;
    }
    */
}
