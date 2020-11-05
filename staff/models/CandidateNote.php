<?php
namespace staff\models;

use Yii;

/**
 * This is the model class for table "CandidateNote".
 * It extends from \common\models\CandidateNote but with custom functionality for this application module
 */
class CandidateNote extends \common\models\CandidateNote {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return parent::fields();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\CandidateNote")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\staff\models\Staff")
    {
        return parent::getStaff($modelClass);
    }
}
