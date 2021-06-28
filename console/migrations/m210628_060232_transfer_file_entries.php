<?php

use yii\db\Migration;

/**
 * Class m210628_060232_transfer_file_entries
 */
class m210628_060232_transfer_file_entries extends Migration
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

        $this->createTable ('{{%transfer_file_entry}}', [
            'tfe_uuid' => $this->char (60),
            'transfer_file_id' => $this->integer (11),
            'status' => $this->string (50),
            'status_description' => $this->string (50),
            'section_index' => $this->string (50),
            'transfer_method' => $this->string (50),
            'credit_amount' => $this->decimal (10, 3),
            'credit_currency' => $this->char (3),
            'exchange_rate' => $this->decimal (10, 3),
            'dealRefNo' => $this->string (100),
            'value_date' => $this->string (100)->null (),
            'debit_account_no' => $this->string (50),
            'credit_account_no' => $this->string (50),
            'debit_narrative' => $this->integer (11)->comment ('transfer_id'),
            'credit_narrative' => $this->integer (11)->comment ('tc_id'),
            'payment_details_1' => $this->string (100),
            'payment_details_2' => $this->string (100),
            'payment_details_3' => $this->string (100),
            'payment_details_4' => $this->string (100),
            'beneficiary_name' => $this->string (100),
            'beneficiary_address_line_1' => $this->string (100),
            'beneficiary_address_line_2' => $this->string (100),
            'beneficiary_bank_name' => $this->string (100),
            'beneficiary_bank_address_1' => $this->string (100),
            'beneficiary_bank_address_2' => $this->string (100),
            'beneficiary_bank_address_3' => $this->string (100),
            'swift' => $this->string (50),
            'intermediary_account' => $this->string (100),
            'intermediary_swift' => $this->string (50),
            'intrmediary_name' => $this->string (100),
            'intermediary_address_1' => $this->string (100),
            'intermediary_address_2' => $this->string (100),
            'intermediary_address_3' => $this->string (100),
            'charges_type' => $this->string (10),
            'sort_code' => $this->string (100),
            'BIC_code' => $this->string (100),
            'IBAN' => $this->string (100),
            'ABA_routing_code' => $this->string (100),
            'created_by' => $this->integer (11),
            'updated_by' => $this->integer (11),
            'created_at' => $this->dateTime (),
            'updated_at' => $this->dateTime (),
        ], $tableOptions);

        $this->addPrimaryKey ('PK', 'transfer_file_entry', 'tfe_uuid');

        $this->createIndex (
            'idx-transfer_file_entry-created_by',
            'transfer_file_entry',
            'created_by'
        );

        $this->addForeignKey (
            'fk-transfer_file_entry-created_by',
            'transfer_file_entry',
            'created_by',
            'admin',
            'admin_id',
            'RESTRICT',
            'RESTRICT'
        );

        $this->createIndex (
            'idx-transfer_file_entry-updated_by',
            'transfer_file_entry',
            'updated_by'
        );

        $this->addForeignKey (
            'fk-transfer_file_entry-updated_by',
            'transfer_file_entry',
            'updated_by',
            'admin',
            'admin_id',
            'RESTRICT',
            'RESTRICT'
        );

        $this->createIndex (
            'idx-transfer_file_entry-transfer_file_id',
            'transfer_file_entry',
            'transfer_file_id'
        );

        $this->addForeignKey (
            'fk-transfer_file_entry-transfer_file_id',
            'transfer_file_entry',
            'transfer_file_id',
            'transfer_file',
            'transfer_file_id',
            'RESTRICT',
            'RESTRICT'
        );

        $this->createIndex (
            'idx-transfer_file_entry-debit_narrative',
            'transfer_file_entry',
            'debit_narrative'
        );

        $this->addForeignKey (
            'fk-transfer_file_entry-debit_narrative',
            'transfer_file_entry',
            'debit_narrative',
            'transfer',
            'transfer_id',
            'RESTRICT',
            'RESTRICT'
        );

        $this->createIndex (
            'idx-transfer_file_entry-credit_narrative',
            'transfer_file_entry',
            'credit_narrative'
        );

        $this->addForeignKey (
            'fk-transfer_file_entry-credit_narrative',
            'transfer_file_entry',
            'credit_narrative',
            'transfer_candidate',
            'tc_id',
            'RESTRICT',
            'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey (
            'fk-transfer_file_entry-credit_narrative',
            'transfer_file_entry'
        );

        $this->dropForeignKey (
            'fk-transfer_file_entry-debit_narrative',
            'transfer_file_entry'
        );

        $this->dropForeignKey (
            'fk-transfer_file_entry-created_by',
            'transfer_file_entry'
        );

        $this->dropForeignKey (
            'fk-transfer_file_entry-updated_by',
            'transfer_file_entry'
        );

        $this->dropForeignKey (
            'fk-transfer_file_entry-transfer_file_id',
            'transfer_file_entry',
        );

        $this->dropTable ('{{%transfer_file_entry}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210628_060232_transfer_file_entry cannot be reverted.\n";

        return false;
    }
    */
}
