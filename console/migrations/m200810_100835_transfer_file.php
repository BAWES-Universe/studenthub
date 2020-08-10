<?php

use yii\db\Migration;

/**
 * Class m200810_100835_transfer_file
 */
class m200810_100835_transfer_file extends Migration
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
        
        $this->createTable('transfer_file', [
            'transfer_file_id' => $this->primaryKey(),
            'transfer_file_s3_path' => $this->string()->notNull(),
            'transfer_file_created_at' => $this->datetime()->notNull(),
            'transfer_file_updated_at' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addColumn(
            'transfer_candidate', 
            'transfer_file_id', 
            $this->integer(11)->after('transfer_confirmation_id')
        );
        
        // creates index for column `tc_id`
        $this->createIndex(
            'idx-transfer_candidate-transfer_file_id',
            'transfer_candidate',
            'transfer_file_id'
        );
        
        // add foreign key for table `tc_id`
        $this->addForeignKey(
            'fk-transfer_candidate-CASCADE',
            'transfer_candidate',
            'transfer_file_id',
            'transfer_file',
            'transfer_file_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        //$this->dropTable('transfer_file');
        
       // $this->dropColumn('transfer_candidate', 'transfer_file_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200810_100835_transfer_file cannot be reverted.\n";

        return false;
    }
    */
}
