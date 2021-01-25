<?php
namespace company\models;


/**
 * This is the model class for table "Country".
 * It extends from \common\models\Country but with custom functionality for this application module
 */
class Country extends \common\models\Country
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAreas($modelClass = "\company\models\Area")
    {
        return parent::getAreas($modelClass);
    }
}
