<?php

namespace common\fixtures;

use Yii;
use yii\test\ActiveFixture;

class WalletUserFixture extends ActiveFixture
{
    public $modelClass = 'common\models\WalletUser';

    public function init()
    {
        $this->db = Yii::$app->walletDb;

        parent::init();
    }
}