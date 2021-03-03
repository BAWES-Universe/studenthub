<?php
namespace candidate\models;


/**
 * This is the model class for table "University".
 * It extends from \common\models\University but with custom functionality for this application module
 */
class University extends \common\models\University {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['total_candidates']);

        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}

