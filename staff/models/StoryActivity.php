<?php

namespace staff\models;

class StoryActivity extends \common\models\StoryActivity
{

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStory($modelClass = "\staff\models\Story")
    {
        return parent::getStory($modelClass);
    }
}