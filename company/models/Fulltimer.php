<?php
namespace company\models;


/**
 * This is the model class for table "Fulltimer".
 * It extends from \common\models\Fulltimer but with custom functionality for this application module
 */
class Fulltimer extends \common\models\Fulltimer {

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
    public function getArea($modelClass = "\company\models\Area")
    {
        return parent::getArea($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\company\models\Country")
    {
        return parent::getNationality($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimerTags($modelClass = "\company\models\FulltimerTags")
    {
        return parent::getFulltimerTags ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\company\models\Note")
    {
        return parent::getNotes($modelClass)
            ->filterNonInternal();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\company\models\Suggestion")
    {
        return parent::getSuggestion ($modelClass);
    }
}
