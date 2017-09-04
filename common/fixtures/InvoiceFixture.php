<?php
namespace common\fixtures;

use yii\test\ActiveFixture;

class InvoiceFixture extends ActiveFixture
{
    public $modelClass = 'admin\models\Invoice';
    public $depends = [
        'common\fixtures\TransferFixture'
    ];
}
