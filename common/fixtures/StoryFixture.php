<?php


namespace common\fixtures;


use yii\test\ActiveFixture;

class StoryFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Story';

    public $depends = [
        'common\fixtures\RequestFixture'
    ];
}