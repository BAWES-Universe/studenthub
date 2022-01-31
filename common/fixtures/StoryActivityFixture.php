<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class StoryActivityFixture extends ActiveFixture
{
    public $modelClass = 'common\models\StoryActivity';
    
    public $depends = [
        'common\fixtures\StoryFixture'
    ];
}
