<?php

use yii\db\Migration;

/**
 * Class m230716_133827_request
 */
class m230716_133827_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('company_request','requesting_for', $this->string()->after('phone_number'));

        $this->addColumn('company','commercial_licence', $this->string()->after('company_logo'));
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
        echo "m230716_133827_request cannot be reverted.\n";

        return false;
    }
    */
}
