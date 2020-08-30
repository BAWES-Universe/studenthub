<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CompanyContactEmailFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CompanyContactEmail';
    
    public $depends = [
        'common\fixtures\CompanyContactFixture'
    ];
}
