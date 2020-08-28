<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateSkillFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateSkill';
    
    public $depends = [
        'common\fixtures\CandidateFixture'
    ];
}
