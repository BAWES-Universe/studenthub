<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateWorkHistoryFixture extends ActiveFixture
{
    public $modelClass = 'common\models\CandidateWorkHistory';
    public $depends = [
        'common\fixtures\CandidateFixture',
        'common\fixtures\StoreFixture'
    ];
}
