<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class FulltimerSkillFixture extends ActiveFixture
{
    public $modelClass = 'common\models\FulltimerSkill';
    
    public $depends = [
        'common\fixtures\FulltimerFixture'
    ];
}
