<?php

use yii\db\Migration;

class m170428_113508_invoice extends Migration
{
    public function up()
    {
        $this->dropTable('invoice_candidates');
        $this->dropTable('invoice');
        
        $this->addColumn('transfer', 'total', $this->decimal(12, 3)->after('company_id'));
        $this->addColumn('transfer', 'company_total', $this->decimal(12, 3)->after('total'));
        $this->addColumn('transfer', 'payment_received_on', $this->date()->after('company_total'));
        $this->addColumn('transfer', 'parent_transfer_id', $this->integer(11)->after('transfer_id'));
        
        $this->createIndex(
            'idx-transfer-transfer_id',
            'transfer',
            'transfer_id'
        );
        
        $this->addForeignKey(
            'fk-transfer-parent_transfer_id',
            'transfer',
            'parent_transfer_id',
            'transfer',
            'transfer_id',
            'SET NULL'
        );

        $this->addColumn('transfer_candidates', 'candidate_hourly_rate', $this->decimal(10, 2)->after('candidate_id'));
        $this->addColumn('transfer_candidates', 'company_hourly_rate', $this->decimal(10, 2)->after('candidate_hourly_rate'));
        $this->addColumn('transfer_candidates', 'transfer_cost', $this->decimal(10, 3)->after('bonus')); 

        //add new invoice table 

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Owner
        $this->createTable('invoice', [
            'invoice_id' => $this->primaryKey(),
            'transfer_id' => $this->integer(11),
            'invoice_date' => $this->date(),
            'invoice_status' => "Enum('paid', 'unpaid ')"
        ], $tableOptions);

        $this->createIndex(
            'idx-invoice-transfer_id',
            'invoice',
            'transfer_id'
        );
        
        $this->addForeignKey(
            'fk-invoice-transfer_id',
            'invoice',
            'transfer_id',
            'transfer',
            'transfer_id',
            'SET NULL'
        );
    }
}
