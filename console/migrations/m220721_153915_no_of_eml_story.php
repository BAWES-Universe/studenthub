<?php

use yii\db\Migration;

/**
 * Class m220721_153915_no_of_eml_story
 */
class m220721_153915_no_of_eml_story extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('request', 'no_of_employees_per_story', $this->smallInteger (6)->after('request_number_of_employees'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220721_153915_no_of_eml_story cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220721_153915_no_of_eml_story cannot be reverted.\n";

        return false;
    }
    */
}
