<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateNoteFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateNote';
    
    public $depends = [
        'common\fixtures\CandidateFixture',
        'common\fixtures\StaffFixture'
    ];
}
