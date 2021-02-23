<?php
namespace common\fixtures;

use yii\test\ActiveFixture;


class InvitationFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Invitation';

    public $depends = [
        'common\fixtures\CompanyFixture',
        'common\fixtures\RequestFixture',
        'common\fixtures\StaffFixture',
        'common\fixtures\CandidateFixture'
    ];
}
