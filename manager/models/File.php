<?php


namespace manager\models;


class File extends \common\models\File
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\manager\models\Company")
    {
        return parent::getCompany($modelClass);
    }
}