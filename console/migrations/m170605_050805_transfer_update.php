<?php

use yii\db\Migration;
use common\models\Transfer;
class m170605_050805_transfer_update extends Migration
{
    public function up()
    {
        $this->execute("update transfer set transfer_status = '".Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS."' where transfer_status = '".Transfer::STATUS_PAYMENT_RECEIVED."'");
    }

    public function down()
    {
        echo "m170605_050805_transfer_update cannot be reverted.\n";

        return false;
    }
}
