<?php

use yii\db\Migration;

class m170420_125428_candidate_university extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'university_id', $this->integer(11)->after('bank_id'));

        // creates index for column `company_id`
        $this->createIndex(
            'idx-candidate-university_id',
            'candidate',
            'university_id'
        );

        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-candidate-university_id',
            'candidate',
            'university_id',
            'university',
            'university_id',
            'SET NULL'
        );
    }
}
