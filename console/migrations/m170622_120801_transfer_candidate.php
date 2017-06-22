<?php

use yii\db\Migration;

class m170622_120801_transfer_candidate extends Migration
{
    public function safeUp()
    {
        $this->addColumn('transfer_candidate', 'store_id', $this->integer(11)->after('candidate_id'));  
        $this->addColumn('transfer_candidate', 'store_name', $this->string(100)->after('store_id'));  
        $this->addColumn('transfer_candidate', 'company_id', $this->integer(11)->after('store_name'));  
        $this->addColumn('transfer_candidate', 'company_name', $this->string(100)->after('company_id'));  
        $this->addColumn('transfer_candidate', 'company_email', $this->string(100)->after('company_name'));  

        $this->createIndex(
            'idx-transfer_candidate-store_id',
            'transfer_candidate',
            'store_id'
        );
        
        $this->addForeignKey(
            'fk-transfer_candidate-store_id',
            'transfer_candidate',
            'store_id',
            'store',
            'store_id',
            'SET NULL'
        );

        $this->createIndex(
            'idx-transfer_candidate-company_id',
            'transfer_candidate',
            'company_id'
        );
        
        $this->addForeignKey(
            'fk-transfer_candidate-company_id',
            'transfer_candidate',
            'company_id',
            'company',
            'company_id',
            'SET NULL'
        );
    }
}
