<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class FulltimerTagsFixture extends ActiveFixture
{
    public $modelClass = 'common\models\FulltimerTags';


    public $depends = [
        'common\fixtures\FulltimerFixture'
    ];
}
