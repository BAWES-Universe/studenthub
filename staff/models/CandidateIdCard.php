<?php

namespace staff\models;

use staff\models\Candidate;

/**
 * This is the model class for table "candidate_id_card".
 *
 * @property integer $id
 * @property integer $candidate_id
 * @property string $expiry_date
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 */
class CandidateIdCard extends \common\models\CandidateIdCard
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

	    $fields['candidate'] = function($model) {
	    	return $this->candidate;
	    };

        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasOne(Candidate::className(), ['candidate_id' => 'candidate_id']);
    }
}