<?php

use yii\db\Migration;

/**
 * Class m240422_091725_request
 */
class m240422_091725_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex('ind-request_skill-request_uuid', 'request_skill', 'request_uuid');

        $this->addColumn("request", "gender", $this->tinyInteger(1)->defaultValue(0));
        $this->addColumn("request", "nationality_id", $this->integer(11));

        $this->createIndex('ind-request-nationality_id', 'request', 'nationality_id');

        $this->addForeignKey(
            'fk-request-nationality_id', 'request', 'nationality_id',
            'country', 'country_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240422_091725_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240422_091725_request cannot be reverted.\n";

        return false;
    }
    */
}
