<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class RequestActiviyFixture extends ActiveFixture
{
    public $modelClass = 'common\models\RequestActiviy';
    
    public $depends = [
        'common\fixtures\RequestFixture'
    ];
}
