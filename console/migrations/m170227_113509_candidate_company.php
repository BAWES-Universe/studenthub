<?php

use yii\db\Migration;

class m170227_113509_candidate_company extends Migration
{
    public function up()
    {
        $this->dropForeignKey(
            'fk-candidate-company_id',
            'candidate'
        );
        
        $this->dropIndex(
            'idx-candidate-company_id',
            'candidate'
        );

        $this->dropColumn('candidate', 'company_id');
    }

    public function down()
    {
    }
}
