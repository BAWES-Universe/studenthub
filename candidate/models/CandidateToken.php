<?php
namespace candidate\models;


/**
 * This is the model class for table "CandidateToken".
 * It extends from \common\models\CandidateToken but with custom functionality for this application module
 *
 */
class CandidateToken extends \common\models\CandidateToken {

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }
}
