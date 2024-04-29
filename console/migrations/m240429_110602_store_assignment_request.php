<?php

use yii\db\Migration;

/**
 * Class m240429_110602_store_assignment_request
 */
class m240429_110602_store_assignment_request extends Migration
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

        $this->createTable('store_assignment_request', [
            "sar_uuid" => $this->char(60),
            "candidate_id" => $this->integer(11),
            "store_id" => $this->integer(11)->null(),
            "currency_code" => $this->char(3)->defaultValue("KWD"),
            "status" => $this->tinyInteger(2)->defaultValue(0),
            'created_at' => $this->datetime(),
            'updated_at' => $this->datetime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'store_assignment_request', 'sar_uuid');

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-store_assignment_request-candidate_id',
            'store_assignment_request',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-store_assignment_request-candidate_id',
            'store_assignment_request',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );

        // creates index for column `store_id`
        $this->createIndex(
            'idx-store_assignment_request-store_id',
            'store_assignment_request',
            'store_id'
        );

        // add foreign key for table `store_id`
        $this->addForeignKey(
            'fk-store_assignment_request-store_id',
            'store_assignment_request',
            'store_id',
            'store',
            'store_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('store_assignment_request');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240429_110602_store_assignment_request cannot be reverted.\n";

        return false;
    }
    */
}
