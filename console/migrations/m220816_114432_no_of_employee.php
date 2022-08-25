<?php

use yii\db\Migration;
use yii\db\Expression;

/**
 * Class m220816_114432_no_of_employee
 */
class m220816_114432_no_of_employee extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        \admin\models\Request::updateAll(['no_of_employees_per_story' => 1], [
            'OR',
            ['no_of_employees_per_story' => 0],
            new Expression('no_of_employees_per_story IS NULL')
        ]);

        $this->alterColumn('request', 'no_of_employees_per_story', $this->smallInteger(6)->defaultValue(1)->notNull());

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
        echo "m220816_114432_no_of_employee cannot be reverted.\n";

        return false;
    }
    */
}
