<?php

use yii\db\Migration;

/**
 * Class m210906_101252_contact_recieve_suggestions
 */
class m210906_101252_contact_recieve_suggestions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('contact','contact_receive_suggestions',$this->tinyInteger(1)->null()->defaultValue(1)->after('contact_receive_notification'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('contact','contact_receive_suggestions');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210906_101252_contact_recieve_suggestions cannot be reverted.\n";

        return false;
    }
    */
}
