<?php

namespace common\fixtures;

use Yii;
use yii\test\ActiveFixture;

class WalletTransferFixture extends ActiveFixture
{
    public $modelClass = 'common\models\WalletTransfer';

    public function init()
    {
        $this->db = Yii::$app->walletDb;

        parent::init();
    }
}