<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class FulltimerFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Fulltimer';


    public $depends = [
        'common\fixtures\CountryFixture',
        'common\fixtures\AreaFixture',
    ];
}
