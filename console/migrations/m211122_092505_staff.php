<?php

use yii\db\Migration;

/**
 * Class m211122_092505_staff
 */
class m211122_092505_staff extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('staff')
            ->getColumn('staff_role');

        if (!$columnData) {

            $this->addColumn (
                'staff',
                'staff_role',
                $this->tinyInteger (1)
                    ->defaultValue (1)
                    ->after ('staff_password_reset_token')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211122_092505_staff cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211122_092505_staff cannot be reverted.\n";

        return false;
    }
    */
}
