<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class FulltimerExperienceFixture extends ActiveFixture
{
    public $modelClass = 'common\models\FulltimerExperience';
    
    public $depends = [
        'common\fixtures\FulltimerFixture'
    ];
}
