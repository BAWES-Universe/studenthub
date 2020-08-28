<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class RequestFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Request';
    
    public $depends = [
        'common\fixtures\CompanyFixture'
    ];
}
