<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class StoreFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Store';
    public $depends = [
        'common\fixtures\CompanyFixture'
    ];
}
