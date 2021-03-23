<?php
namespace company\models;


/**
 * This is the model class for table "FulltimerTags".
 * It extends from \common\models\FulltimerTags but with custom functionality for this application module
 */
class FulltimerTags extends \common\models\FulltimerTags
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
    public function getCountry($modelClass = "\company\models\Country")
    {
        return parent::getCountry($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\company\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }
}

