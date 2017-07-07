<?php

use yii\db\Migration;

class m170602_062115_bank_transfer_type_field_in_bank_table extends Migration
{
    public function up()
    {
        $this->addColumn('bank','bank_transfer_type',$this->string(225)->after('bank_address')->notNull());
    }

    public function down()
    {
        $this->dropColumn('bank','bank_transfer_type');

        return false;
    }
}
