<?php

use yii\db\Migration;

/**
 * Class m240203_060625_block_ip
 */
class m240203_060625_block_ip extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate", "ip_address", $this->string(45));
        $this->addColumn("contact", "ip_address", $this->string(45));
        $this->addColumn("fulltimer", "ip_address", $this->string(45));
        $this->addColumn("inspector", "ip_address", $this->string(45));
        $this->addColumn("staff", "ip_address", $this->string(45));

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        //table: blocked_ip

        $this->createTable('{{%blocked_ip}}', [
            'ip_uuid' => $this->char(60), // used as reference id
            'ip_address' => $this->string(45),
            'note' => $this->string(255),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'blocked_ip', 'ip_uuid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240203_060625_block_ip cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240203_060625_block_ip cannot be reverted.\n";

        return false;
    }
    */
}
