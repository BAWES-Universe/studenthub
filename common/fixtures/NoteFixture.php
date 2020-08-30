<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class NoteFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Note';
    
    public $depends = [
        'common\fixtures\CompanyFixture'
    ];
}
