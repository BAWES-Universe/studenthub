<?php
namespace manager\models;


/**
 * This is the model class for table "Area".
 * It extends from \common\models\Area but with custom functionality for this application module
 */
class Area extends \common\models\Area
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\manager\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\manager\models\Country")
    {
        return parent::getCountry($modelClass);
    }
}


