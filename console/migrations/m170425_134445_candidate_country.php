<?php

use yii\db\Migration;

class m170425_134445_candidate_country extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'country_id', $this->integer(11)->after('university_id'));

        $this->createIndex(
            'idx-candidate-country_id',
            'candidate',
            'country_id'
        );
        
        $this->addForeignKey(
            'fk-candidate-country_id',
            'candidate',
            'country_id',
            'country',
            'country_id',
            'SET NULL'
        );
    }
}
