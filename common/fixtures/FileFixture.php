<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class FileFixture extends ActiveFixture
{
    public $modelClass = 'common\models\File';
    
    public $depends = [
        'common\fixtures\CompanyFixture'
    ];
}
