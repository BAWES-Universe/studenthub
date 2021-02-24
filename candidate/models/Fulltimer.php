<?php

namespace candidate\models;


class Fulltimer extends \common\models\CompanyContact
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return parent::getCountry($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\common\models\Area")
    {
        return parent::getArea($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNationality($modelClass = "\common\models\Country")
    {
        return parent::getNationality($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimerTags($modelClass = "\common\models\FulltimerTags")
    {
        return parent::getFulltimerTags($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\candidate\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\candidate\models\Suggestion")
    {
        return parent::getSuggestion($modelClass);
    }
}