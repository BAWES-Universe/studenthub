<?php
namespace common\fixtures;

use yii\test\ActiveFixture;
use common\fixtures\RequestFixture;
use common\fixtures\FulltimerFixture;
use common\fixtures\CompanyFixture;
use common\fixtures\NoteFixture;

class SuggestionFixture extends ActiveFixture
{
    public $modelClass = 'common\models\Suggestion';

    public $depends = [
        'common\fixtures\CompanyFixture',
        'common\fixtures\RequestFixture',
        'common\fixtures\NoteFixture',
        'common\fixtures\FulltimerFixture'
    ];
}
