<?php

use yii\db\Migration;

class m170228_144703_company_invoice extends Migration
{
    public function up()
    {
        $this->createTable('invoice', [
            'invoice_id' => $this->primaryKey(),
            'company_id' => $this->integer(),
            'total' => $this->decimal(12, 3),
            'invoice_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'invoice_created_at' => $this->datetime()->notNull(),
            'invoice_updated_at' => $this->datetime()->notNull()
        ]);

        $this->createIndex(
            'idx-invoice-company_id',
            'invoice',
            'company_id'
        );
        
        $this->addForeignKey(
            'fk-invoice-company_id',
            'invoice',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        $this->createTable('invoice_candidates', [
            'ic_id' => $this->primaryKey(),
            'invoice_id' => $this->integer(),
            'candidate_id' => $this->integer(),
            'hourly_rate' => $this->decimal(10, 2),
            'hours' => $this->decimal(10, 2),
            'bonus' => $this->decimal(10, 3),
            'ic_created_at' => $this->datetime()->notNull(),
            'ic_updated_at' => $this->datetime()->notNull()
        ]);

        $this->createIndex(
            'idx-ic-invoice_id',
            'invoice_candidates',
            'invoice_id'
        );

        $this->addForeignKey(
            'fk-ic-invoice_id',
            'invoice_candidates',
            'invoice_id',
            'invoice',
            'invoice_id',
            'CASCADE'
        );

        $this->createIndex(
            'idx-ic-candidate_id',
            'invoice_candidates',
            'candidate_id'
        );
        
        $this->addForeignKey(
            'fk-ic-candidate_id',
            'invoice_candidates',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );
    }

    public function down()
    {
        echo "m170228_144703_company_invoice cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
