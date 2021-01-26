<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class ContactEmailFixture extends ActiveFixture
{
    public $modelClass = 'common\models\ContactEmail';
    
    public $depends = [
        'common\fixtures\ContactFixture'
    ];
}
