<?php

use yii\db\Migration;

/**
 * Class m231009_105032_email
 */
class m231009_105032_email extends Migration
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

        $this->createTable('email_campaign', [
            "campaign_uuid"=> $this->char(60)->notNull(), // used as reference id
            'subject' => $this->string(),
            'message'=> $this->text(),
            "progress" => $this->integer(11)->defaultValue(0),
            "status" => $this->tinyInteger(1)->defaultValue(0),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'email_campaign', 'campaign_uuid');

        $this->createTable('{{%email_campaign_filter}}', [
            'cf_uuid' => $this->char(60), // used as reference id
            'campaign_uuid' => $this->char(60)->notNull(), // used as reference id
            'param' => $this->string(50),
            'value' => $this->string(100),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'email_campaign_filter', 'cf_uuid');

        $this->createIndex('ind-email_campaign_filter-campaign_uuid', 'email_campaign_filter', 'campaign_uuid');

        $this->addForeignKey(
            'fk-email_campaign_filter-customer_id', 'email_campaign_filter',
            'campaign_uuid', 'email_campaign', 'campaign_uuid', "CASCADE"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("email_campaign_filter");
        $this->dropTable("email_campaign");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231009_105032_email cannot be reverted.\n";

        return false;
    }
    */
}
