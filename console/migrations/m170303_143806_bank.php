<?php

use yii\db\Migration;

class m170303_143806_bank extends Migration
{
    public function up()
    {
        $this->createTable('bank', [
            'bank_id' => $this->primaryKey(),
            'bank_name' => $this->string(100)
        ]);
    }

    public function down()
    {
        $this->dropTable('bank');
    }
}
