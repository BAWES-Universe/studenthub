<?php

use yii\db\Migration;

/**
 * Class m210414_080805_invitation_time
 */
class m210414_080805_invitation_time extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('invitation', 'invitation_app_seen_at', $this->dateTime ()->null ()->after('invitation_status'));
        $this->addColumn('invitation', 'invitation_email_seen_at', $this->dateTime ()->null ()->after('invitation_app_seen_at'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210414_080805_invitation_time cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210414_080805_invitation_time cannot be reverted.\n";

        return false;
    }
    */
}
