<?php

use yii\db\Migration;

/**
 * Class m241001_072924_advice
 */
class m241001_072924_advice extends Migration
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

        $this->createTable('{{%transfer_bank_advice}}', [
            "tba_uuid" => $this->char(60),
            "serial_no"=> $this->integer(11),
            "file_path" => $this->string(),
            "created_by" => $this->integer(11),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
            "is_deleted" => $this->boolean()->defaultValue(false)
        ], $tableOptions);

        $this->addPrimaryKey('pk-transfer_bank_advice-tba_uuid', 'transfer_bank_advice', "tba_uuid");

        // creates index for column `created_by`
        $this->createIndex(
            'idx-transfer_bank_advice-created_by',
            'transfer_bank_advice',
            'created_by'
        );

        // add foreign key for table `admin`
        $this->addForeignKey(
            'fk-transfer_bank_advice-admin_id',
            'transfer_bank_advice',
            'created_by',
            'admin',
            'admin_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%transfer_bank_advice}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241001_072924_advice cannot be reverted.\n";

        return false;
    }
    */
}
