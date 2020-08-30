<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class TransferFileFixture extends ActiveFixture
{
    public $modelClass = 'common\models\TransferFile';
    
    public $depends = [
        'common\fixtures\CompanyFixture'
    ];
}
