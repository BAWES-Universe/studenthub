<?php


namespace staff\models;


class File extends \common\models\File
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }
}