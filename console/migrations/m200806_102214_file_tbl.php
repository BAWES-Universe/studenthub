<?php

use yii\db\Migration;

/**
 * Class m200806_102214_file_tbl
 */
class m200806_102214_file_tbl extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('file', [
            'file_uuid' => $this->char(60),
            'company_id' => $this->integer(),
            'file_title' => $this->string()->notNull(),
            'file_description' => $this->text()->null(),
            'file_name' => $this->string(),
            'file_type' => $this->char(10),
            'file_size' => $this->integer(),
            'file_s3_path' => $this->string(225),
            'file_created_datetime' => $this->datetime()->notNull()
        ]);

        $this->createIndex(
            'idx-file-company_id',
            'file',
            'company_id'
        );

        $this->addForeignKey(
            'fk-file-company_id',
            'file',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200806_102214_file_tbl cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200806_102214_file_tbl cannot be reverted.\n";

        return false;
    }
    */
}
