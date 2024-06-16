<?php

use yii\db\Migration;

/**
 * Class m240616_092450_session_status
 */
class m240616_092450_session_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_working_hour", "note", $this->text());
        $this->addColumn("candidate_working_hour", "status",  $this->smallInteger(1)->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240616_092450_session_status cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240616_092450_session_status cannot be reverted.\n";

        return false;
    }
    */
}
