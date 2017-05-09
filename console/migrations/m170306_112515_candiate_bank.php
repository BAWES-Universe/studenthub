<?php

use yii\db\Migration;

class m170306_112515_candiate_bank extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'bank_id', $this->integer(11)->after('store_id'));

        $this->createIndex(
            'idx-candidate-bank_id',
            'candidate',
            'bank_id'
        );
        
        $this->addForeignKey(
            'fk-candidate-bank_id',
            'candidate',
            'bank_id',
            'bank',
            'bank_id',
            'CASCADE'
        );

        $this->addColumn('candidate', 'candidate_iban', $this->string(100)->after('bank_id'));
    }

    public function down()
    {
        $this->dropForeignKey(
            'fk-candidate-bank_id',
            'candidate'
        );
        
        $this->dropIndex(
            'idx-candidate-bank_id',
            'candidate'
        );

        $this->dropColumn('candidate', 'bank_id');

        $this->dropColumn('candidate', 'candidate_iban');
    }
}
