<?php

use yii\db\Migration;

class m170223_132254_candidate_to_store extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'store_id', $this->integer(11)->after('company_id'));

        $this->createIndex(
            'idx-candidate-store_id',
            'candidate',
            'store_id'
        );
        
        $this->addForeignKey(
            'fk-candidate-store_id',
            'candidate',
            'store_id',
            'store',
            'store_id',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey(
            'fk-candidate-store_id',
            'candidate'
        );
        
        $this->dropIndex(
            'idx-candidate-store_id',
            'candidate'
        );

        $this->dropColumn('candidate', 'store_id');
    }
}
