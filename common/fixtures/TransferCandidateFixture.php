<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class TransferCandidateFixture extends ActiveFixture
{
    public $modelClass = 'common\models\TransferCandidate';
    
    public $depends = [
        'common\fixtures\TransferFixture',
        'common\fixtures\CandidateFixture',
        'common\fixtures\StoreFixture',
    ];
}
