<?php

use yii\db\Migration;

/**
 * Class m250313_173340_campaign_schedule
 */
class m250313_173340_campaign_schedule extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("email_campaign", "trigger_date_time", $this->dateTime()
            ->after("progress")->null());

        $this->addColumn("email_campaign", "last_trigger_date_time", $this->dateTime()
            ->after("trigger_date_time")->null());

        $this->addColumn("email_campaign", "is_recurring", $this->boolean()
            ->after("last_trigger_date_time")->defaultValue(false));

        $this->addColumn("email_campaign", "trigger_period",
            $this->integer()->after("is_recurring"));

        $this->addColumn("email_campaign", "target",
            $this->string(10)->after("trigger_period")->defaultValue("both"));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250313_173340_campaign_schedule cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250313_173340_campaign_schedule cannot be reverted.\n";

        return false;
    }
    */
}
