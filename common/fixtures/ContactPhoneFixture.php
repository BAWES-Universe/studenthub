<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class ContactPhoneFixture extends ActiveFixture
{
    public $modelClass = 'common\models\ContactPhone';
    
    public $depends = [
        'common\fixtures\ContactFixture'
    ];
}
