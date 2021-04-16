<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class ContactInvitationFixture extends ActiveFixture
{
    public $modelClass = 'common\models\ContactInvitation';

    public $depends = [
        'common\fixtures\ContactFixture'
    ];
}
