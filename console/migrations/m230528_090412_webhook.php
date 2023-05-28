<?php

use yii\db\Migration;

/**
 * Class m230528_090412_webhook
 */
class m230528_090412_webhook extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%webhook}}', [
            'webhook_id' => $this->primaryKey(),
            'event' => $this->string(50)->notNull(),
            'endpoint' => $this->string()->notNull(),
            'method' => "Enum('GET', 'POST', 'PUT', 'PATCH', 'DELETE')",
            'created_by' => $this->integer(11),
            'updated_by' => $this->integer(11),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        // creates index for column `event`
        $this->createIndex(
            'idx-webhook-event',
            'webhook',
            'event'
        );

        // add foreign key for table `created_by`
        $this->addForeignKey(
            'fk-webhook-created_by',
            'webhook',
            'created_by',
            'staff',
            'staff_id',
            'SET NULL'
        );

        // add foreign key for table `updated_by`
        $this->addForeignKey(
            'fk-webhook-updated_by',
            'webhook',
            'updated_by',
            'staff',
            'staff_id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230528_090412_webhook cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230528_090412_webhook cannot be reverted.\n";

        return false;
    }
    */
}
