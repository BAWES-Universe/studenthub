<?php

use yii\db\Migration;

/**
 * Class m240227_045347_mail_log
 */
class m240227_045347_mail_log extends Migration
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

        $this->createTable('{{%mail_log}}', [
            'mail_uuid' => $this->char(60),
            "from" => $this->string(),
            "to" => $this->string(),
            "subject" => $this->string(),
            "app" => $this->string(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'mail_log', 'mail_uuid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240227_045347_mail_log cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240227_045347_mail_log cannot be reverted.\n";

        return false;
    }
    */
}
