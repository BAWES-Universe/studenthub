<?php

use yii\db\Migration;

/**
 * Class m210111_064411_contact_token_tbl
 */
class m210111_064411_contact_token_tbl extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        /**
         * Admin Tokens
         */
        $this->createTable('contact_token', [
            'token_id' => $this->primaryKey(),
            'contact_uuid' => $this->char(60)->notNull(),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);
        // creates index for column `admin_id`
        $this->createIndex(
            'idx-contact_token-contact_uuid',
            'contact_token',
            'contact_uuid'
        );
        // add foreign key for table `admin`
        $this->addForeignKey(
            'fk-contact_token-contact_uuid',
            'contact_token',
            'contact_uuid',
            'contact',
            'contact_uuid'

        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-contact_token-contact_uuid','contact_token');
        $this->dropTable('contact_token');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210111_064411_contact_token_tbl cannot be reverted.\n";

        return false;
    }
    */
}
