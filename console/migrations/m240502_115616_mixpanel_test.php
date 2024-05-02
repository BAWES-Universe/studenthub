<?php

use yii\db\Migration;
use common\models\Setting;

/**
 * Class m240502_115616_mixpanel_test
 */
class m240502_115616_mixpanel_test extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Setting::setConfig('EventManager', 'Test-Mixpanel-Key', "ac62dbe81767f8871f754c7bdf6669d6");
        Setting::setConfig('EventManager', 'Test-Segment-Key', "7oEpdGxjwBMlwBQYuXD7NpYWp4HzDJWh");
        Setting::setConfig('EventManager', 'Test-Segment-Key-Wallet', "7oEpdGxjwBMlwBQYuXD7NpYWp4HzDJWh");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240502_115616_mixpanel_test cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240502_115616_mixpanel_test cannot be reverted.\n";

        return false;
    }
    */
}
