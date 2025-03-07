<?php

use yii\db\Migration;

/**
 * Class m250120_201548_id_request
 */
class m250120_201548_id_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('candidate_id_request');

        if ($columnData) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%candidate_id_request}}', [
            "cir_uuid" => $this->char(60),
            "candidate_ids" => $this->text(),
            "status" => $this->string(100)->defaultValue("pending"),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
            "created_by" => $this->integer(11),
            "updated_by" => $this->integer(11),
        ], $tableOptions);

        $this->addPrimaryKey('pk-candidate_id_request-cir_uuid',
            'candidate_id_request', "cir_uuid");

        // creates index for column `updated_by`
        $this->createIndex(
            'idx-candidate_id_request-updated_by',
            'candidate_id_request',
            'updated_by'
        );

        // add foreign key for table `updated_by`
        $this->addForeignKey(
            'fk-candidate_id_request-updated_by',
            'candidate_id_request',
            'updated_by',
            'staff',
            'staff_id'
        );

        // creates index for column `created_by`
        $this->createIndex(
            'idx-candidate_id_request-created_by',
            'candidate_id_request',
            'created_by'
        );

        // add foreign key for table `created_by`
        $this->addForeignKey(
            'fk-candidate_id_request-created_by',
            'candidate_id_request',
            'created_by',
            'staff',
            'staff_id'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250120_201548_id_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250120_201548_id_request cannot be reverted.\n";

        return false;
    }
    */
}
