<?php

use yii\db\Migration;

/**
 * Class m220622_071133_staff_table_notification_field
 */
class m220622_071133_staff_table_notification_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn (
            'staff',
            'staff_notification',
            $this->tinyInteger(1)
                ->defaultValue(1)
                ->after ('staff_status')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn (
            'staff',
            'staff_notification'
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220622_071133_staff_table_notification_field cannot be reverted.\n";

        return false;
    }
    */
}
