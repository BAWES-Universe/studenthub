<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class CandidateFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Candidate';
    
    public $depends = [
        'common\fixtures\BankFixture',
        'common\fixtures\CountryFixture',
        'common\fixtures\StoreFixture',
        'common\fixtures\UniversityFixture'
    ];
}
