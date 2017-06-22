<?php

use yii\db\Migration;
use common\models\Candidate;
use common\models\TransferCandidate;

class m170622_133503_fill_new_fields extends Migration
{
    public function safeUp()
    {
        $transfer_candidates = TransferCandidate::find()
            ->where('store_name IS NULL')
            ->all();

        foreach ($transfer_candidates as $key => $value) 
        {
            $candidate = Candidate::findOne($value->candidate_id);

            if(empty($candidate) || empty($candidate->store) || empty($candidate->store->company))
                continue;

            $value->store_id = $candidate->store_id;
            $value->store_name = $candidate->store->store_name;
            $value->company_id = $candidate->store->company_id;
            $value->company_name = $candidate->store->company->company_name;
            $value->company_email = $candidate->store->company->company_email;
            $value->save(false);
        }
    }
}
