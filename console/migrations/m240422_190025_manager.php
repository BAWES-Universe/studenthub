<?php

use yii\db\Migration;

/**
 * Class m240422_190025_manager
 */
class m240422_190025_manager extends Migration
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

        $this->createTable('store_manager', [
            "store_manager_uuid" => $this->char(60),
            'company_id' => $this->integer(),
            "store_id" => $this->integer(),
            'name' => $this->string(100)->notNull(),
            'email' => $this->string(100),
            'new_email' => $this->string(100),
            "phone_number" => $this->string(100),
            "password_hash" => $this->string(),
            "password_reset_token" => $this->string(),
            "auth_key" => $this->string(),
            "email_verification" => $this->boolean()->defaultValue(false),
            'limit_email'=> $this->datetime(),
            'created_at' => $this->datetime(),
            'updated_at' => $this->datetime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'store_manager', 'store_manager_uuid');

        // creates index for column `company_id`
        $this->createIndex(
            'idx-store_manager-company_id',
            'store_manager',
            'company_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-store_manager-CASCADE',
            'store_manager',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        // creates index for column `store_id`
        $this->createIndex(
            'idx-store_manager-store_id',
            'store_manager',
            'store_id'
        );

        // add foreign key for table `store_id`
        $this->addForeignKey(
            'fk-store_manager-store_id',
            'store_manager',
            'store_id',
            'store',
            'store_id',
            'CASCADE'
        );

        $this->createTable('manager_token', [
            'token_uuid' => $this->char(60),
            'store_manager_uuid' => $this->char(60),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'manager_token', 'token_uuid');

        // creates index for column `store_manager_uuid`
        $this->createIndex(
            'idx-manager_token-store_manager_uuid',
            'manager_token',
            'store_manager_uuid'
        );
        
        // add foreign key for table `store_manager`
        $this->addForeignKey(
            'fk-manager_token-store_manager_uuid',
            'manager_token',
            'store_manager_uuid',
            'store_manager',
            'store_manager_uuid',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("SET foreign_key_checks = 0;");
        $this->dropTable("store_manager");
        $this->dropTable("manager_token");
        $this->execute("SET foreign_key_checks = 1;");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240422_190025_manager cannot be reverted.\n";

        return false;
    }
    */
}
