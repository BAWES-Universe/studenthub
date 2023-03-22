<?php


namespace staff\models;


class Fulltimer extends \common\models\Fulltimer {

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\staff\models\Country")
    {
        return parent::getCountry($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\staff\models\Area")
    {
        return parent::getArea($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\staff\models\Country")
    {
        return parent::getNationality($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimerTags($modelClass = "\common\models\FulltimerTags")
    {
        return parent::getFulltimerTags ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\staff\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\staff\models\Suggestion")
    {
        return parent::getSuggestion ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = "\staff\models\University")
    {
        return parent::getUniversity ($modelClass);
    }
}
