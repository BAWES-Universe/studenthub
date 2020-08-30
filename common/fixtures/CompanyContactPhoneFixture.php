<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CompanyContactPhoneFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CompanyContactPhone';
    
    public $depends = [
        'common\fixtures\CompanyContactFixture'
    ];
}
