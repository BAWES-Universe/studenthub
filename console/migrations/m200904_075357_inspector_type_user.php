<?php

use yii\db\Migration;

/**
 * Class m200904_075357_inspector_type_user
 */
class m200904_075357_inspector_type_user extends Migration
{
    public function up()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Owner
        $this->createTable('inspector', [
            'inspector_uuid' => $this->char(60),
            'inspector_name' => $this->string()->notNull(),
            'inspector_email' => $this->string()->notNull()->unique(),
            'inspector_auth_key' => $this->string(32)->notNull(),
            'inspector_password_hash' => $this->string()->notNull(),
            'inspector_password_reset_token' => $this->string()->unique(),
            'inspector_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'inspector_deleted' => $this->smallInteger()->notNull()->defaultValue(0),
            'inspector_created_at' => $this->datetime()->notNull(),
            'inspector_updated_at' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'inspector', 'inspector_uuid');

        $this->createTable('inspector_token', [
            'token_uuid' => $this->char(60),
            'inspector_uuid' => $this->char(60)->notNull(),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'inspector_token', 'token_uuid');

        // creates index for column `inspector_uuid`
        $this->createIndex(
            'idx-inspector_token-inspector_uuid',
            'inspector_token',
            'inspector_uuid'
        );
        // add foreign key for table `inspector_token`
        $this->addForeignKey(
            'fk-inspector_token-inspector_uuid',
            'inspector_token',
            'inspector_uuid',
            'inspector',
            'inspector_uuid',
            'CASCADE'
        );
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();

    }

    public function down()
    {
        $this->dropTable('inspector');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200904_075357_inspector_type_user cannot be reverted.\n";

        return false;
    }
    */
}
