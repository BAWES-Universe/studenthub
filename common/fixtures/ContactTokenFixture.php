<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class ContactTokenFixture extends ActiveFixture
{
    public $modelClass = 'common\models\ContactToken';

    public $depends = [
        'common\fixtures\ContactFixture'
    ];
}
