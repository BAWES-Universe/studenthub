<?php

namespace common\fixtures;

use Yii;
use yii\test\ActiveFixture;

class WalletBankFixture extends ActiveFixture
{
    public $modelClass = 'common\models\WalletBank';

    public function init()
    {
        $this->db = Yii::$app->walletDb;

        parent::init();
    }
}