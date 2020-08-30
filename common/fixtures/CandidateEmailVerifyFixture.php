<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateEmailVerifyFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateEmailVerifyAttempt';
    
    public $depends = [
        'common\fixtures\CandidateFixture'
    ];
}
