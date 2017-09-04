<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class AdminTokenFixture extends ActiveFixture
{
    public $modelClass = 'common\models\AdminToken';
    public $depends = [
        'common\fixtures\AdminFixture'
    ];
}
