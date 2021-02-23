<?php

use yii\db\Migration;

/**
 * Class m210223_131748_request_location
 */
class m210223_131748_request_location extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('request', 'request_location', $this->string()->after('request_number_of_employees'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210223_131748_request_location cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210223_131748_request_location cannot be reverted.\n";

        return false;
    }
    */
}
