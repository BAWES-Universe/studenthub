<?php

use yii\db\Migration;

class m170704_120143_invoice extends Migration
{
    public function safeUp()
    {
        Yii::$app->db->createCommand('ALTER TABLE invoice AUTO_INCREMENT=26')->execute();

    }
}
