<?php

use yii\db\Migration;

/**
 * Class m240328_182245_bank_transaction
 */
class m240328_182245_bank_transaction extends Migration
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

        $this->createTable('{{%bank_transaction_contact}}', [
            "contact_id" => $this->char(60),
            "name" => $this->string(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'bank_transaction_contact', 'contact_id');

        $this->createTable('{{%bank_transaction}}', [
            'bank_transaction_id' => $this->char(60),
            "contact_id" => $this->char(60),
            "currency_rate" => $this->double(15,8)->defaultValue(null)->null(),
            "currency_code" => $this->char(3)->defaultValue("KWD"),
            "has_attachments"=> $this->boolean()->defaultValue(false),
            "is_reconciled"=> $this->boolean()->defaultValue(false),
            "line_amount_types" => $this->string(100),
            "overpayment_id" => $this->string(),
            "prepayment_id"  => $this->string(),
            "reference" => $this->string(),
            "status"  => $this->string(),
            "status_attribute_string" => $this->string(),
            "sub_total" => $this->double(15, 3),
            "total"=> $this->double(15, 3),
            "total_tax"=> $this->double(15, 3),
            "type" => $this->string(),
            "url" => $this->string(),
            "validation_errors" => $this->string(),
            'date' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'bank_transaction', 'bank_transaction_id');

        // creates index for column `contact_id`
        $this->createIndex(
            'idx-bank_transaction-contact_id',
            'bank_transaction',
            'contact_id'
        );

        // add foreign key for table `contact_id`
        $this->addForeignKey(
            'fk-bank_transaction-contact_id',
            'bank_transaction',
            'contact_id',
            'bank_transaction_contact',
            'contact_id'
        );

        $this->createTable('{{%bank_transaction_line_item}}', [
            'line_item_id' => $this->char(60),
            "bank_transaction_id"=> $this->char(60),
            "account_code" => $this->string(),
            "account_id" => $this->string(),
            "description" => $this->string(),
            "discount_amount"=> $this->double(15, 3),
            "discount_rate"=> $this->double(15, 3),
            "item_code" => $this->string(),
            "line_amount"=> $this->double(15, 3),
            "quantity"=> $this->integer(11),
            "repeating_invoice_id" => $this->string(),
            "tax_amount"=> $this->double(15, 3),
            "tax_type" => $this->string(),
            "unit_amount" => $this->double(15, 3),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'bank_transaction_line_item', 'line_item_id');

        // creates index for column `bank_transaction_id`
        $this->createIndex(
            'idx-bank_transaction_line_item-bank_transaction_id',
            'bank_transaction_line_item',
            'bank_transaction_id'
        );

        // add foreign key for table `bank_transaction_id`
        $this->addForeignKey(
            'fk-bank_transaction_line_item-bank_transaction_id',
            'bank_transaction_line_item',
            'bank_transaction_id',
            'bank_transaction',
            'bank_transaction_id',
            "CASCADE",
            "CASCADE"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240328_182245_bank_transaction cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240328_182245_bank_transaction cannot be reverted.\n";

        return false;
    }
    */
}
