<?php


namespace staff\models;


class Country extends \common\models\Country
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAreas($modelClass = "\staff\models\Area")
    {
        return parent::getAreas($modelClass);
    }

    public function fields()
    {
        $fields = parent::fields();

        unset($fields['total_candidates']);
        return $fields;
    }

}
