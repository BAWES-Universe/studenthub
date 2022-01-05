<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateVideoLogFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateVideoLog';
    
    public $depends = [
        'common\fixtures\CandidateFixture'
    ];
}
