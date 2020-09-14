<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class InspectorTokenFixture extends ActiveFixture
{
    public $modelClass = 'common\models\InspectorToken';
    public $depends = [
        'common\fixtures\InspectorFixture'
    ];
}
