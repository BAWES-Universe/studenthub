<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class StaffTokenFixture extends ActiveFixture
{
    public $modelClass = 'common\models\StaffToken';
    public $depends = [
        'common\fixtures\StaffFixture'
    ];
}
