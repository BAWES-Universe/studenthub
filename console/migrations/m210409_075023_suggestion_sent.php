<?php

use yii\db\Migration;

/**
 * Class m210409_075023_suggestion_sent
 */
class m210409_075023_suggestion_sent extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('suggestion','mail_to_company',$this->tinyInteger(1)->defaultValue(0)->after('suggestion_status'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('suggestion','mail_to_company');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210409_075023_suggestion_sent cannot be reverted.\n";

        return false;
    }
    */
}
