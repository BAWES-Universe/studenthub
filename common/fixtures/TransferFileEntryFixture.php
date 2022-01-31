<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class TransferFileEntryFixture extends ActiveFixture
{
    public $modelClass = 'common\models\TransferFileEntry';
    
    public $depends = [
        'common\fixtures\TransferFileEntryFixture'
    ];
}
