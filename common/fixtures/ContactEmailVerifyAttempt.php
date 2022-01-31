<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class ContactEmailVerifyAttemptFixture extends ActiveFixture
{
    public $modelClass = 'common\models\ContactEmailVerifyAttempt';
    
    public $depends = [
        'common\fixtures\ContactFixture'
    ];
}
