<?php

use yii\db\Migration;
use common\models\Setting;

/**
 * Class m230314_125154_setting
 */
class m230314_125154_setting extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%setting}}', [
            'setting_uuid'=> $this->char(60),
            'code' => $this->string(128)->notNull()->comment('module identifier'),
            'key' => $this->string(128)->notNull(),
            'value' => $this->text(),
            'serialized' => $this->tinyInteger(1)->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'setting', 'setting_uuid');

        Setting::setConfig('EventManager', 'Mixpanel-Status', "enabled");

        Setting::setConfig('EventManager', 'Segment-Status', "enabled");

        if(YII_ENV == 'prod') {
            Setting::setConfig('EventManager', 'Mixpanel-Key', "bfe2ac5e039a3d8d1c8e281967d6f954");
            Setting::setConfig('EventManager', 'Segment-Key', "WZc7uvfkM1uhsjT1Eie6PONXFZK3ME15");
            Setting::setConfig('EventManager', 'Segment-Key-Wallet', "j18MpMF6fvZzmc6bvF0VjlTajAlKwai2");
        } else {
            Setting::setConfig('EventManager', 'Mixpanel-Key', "ac62dbe81767f8871f754c7bdf6669d6");
            Setting::setConfig('EventManager', 'Segment-Key', "7oEpdGxjwBMlwBQYuXD7NpYWp4HzDJWh");
            Setting::setConfig('EventManager', 'Segment-Key-Wallet', "7oEpdGxjwBMlwBQYuXD7NpYWp4HzDJWh");
        }
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
        echo "m230314_125154_setting cannot be reverted.\n";

        return false;
    }
    */
}
