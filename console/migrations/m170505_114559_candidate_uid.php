<?php

use yii\db\Migration;
use common\models\Candidate;

class m170505_114559_candidate_uid extends Migration
{
    public function up()
    {
        $this->addColumn("candidate", "candidate_uid", $this->string(20)->after('candidate_id'));

        //generate uid for all candidates 

        $candidates = Candidate::find()
            ->where('candidate_uid IS NULL')
            ->all();

        foreach ($candidates as $key => $value) 
        {
            $value->save(false);
        }
    }
}
