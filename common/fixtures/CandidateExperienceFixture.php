<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateExperienceFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateExperience';
    
    public $depends = [
        'common\fixtures\CandidateFixture'
    ];
}
