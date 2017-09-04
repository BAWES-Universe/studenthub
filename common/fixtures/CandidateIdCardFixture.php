<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateIdCardFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateIdCard';
    public $depends = [
        'common\fixtures\CandidateFixture'
    ];
}
