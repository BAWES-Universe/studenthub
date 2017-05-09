<?php

use yii\db\Migration;

class m170427_123738_candidate_photo extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'candidate_personal_photo', $this->string(255)->after('candidate_name_ar'));
    }
}
