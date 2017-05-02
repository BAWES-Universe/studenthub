<?php

use yii\db\Migration;

class m170502_133510_company_email extends Migration
{
    public function up()
    {
        $this->dropIndex('company_email', 'company');
    }
}
