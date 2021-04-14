<?php

use yii\db\Migration;

/**
 * Class m210407_160447_company_email_update
 */
class m210407_160447_company_email_update extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('contact', 'contact_new_email', $this->string()->after('contact_email'));
        $this->addColumn ('contact', 'contact_email_verification', $this->boolean()->defaultValue (false)->after('contact_new_email'));
        $this->addColumn ('contact', 'contact_limit_email', $this->dateTime()->after('contact_email_verification'));

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contact_email_verify_attempt}}', [
            'ceva_uuid' => $this->char(60),
            'email' => $this->string(50),
            'code' => $this->string(60),
            'ip_address' => $this->string(45),
            'created_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'contact_email_verify_attempt', 'ceva_uuid');

        $this->db->createCommand ('UPDATE contact SET contact_email_verification=1')->execute ();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable ("{{%contact_email_verify_attempt}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210407_160447_company_email_update cannot be reverted.\n";

        return false;
    }
    */
}
