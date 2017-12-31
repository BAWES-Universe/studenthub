<?php

use yii\db\Migration;

class m171107_071516_company_hourly_rate extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%company}}', 
            'company_hourly_rate', 
            $this->decimal(10, 3)->after('company_password_reset_token')
        );
        
        $this->addColumn(
            '{{%company}}', 
            'company_bonus_commission', 
            $this->decimal(6, 3)->after('company_hourly_rate')->comment('% of bonus as profit')
        );
        
        $this->addColumn(
            '{{%transfer_candidate}}', 
            'bonus_commission', 
            $this->decimal(10, 3)->after('bonus')->comment('bonus as profit in KWD')
        );
    }

    public function safeDown()
    {
        $this->dropColumn(
            '{{%company}}', 
            'company_hourly_rate'
        );
        
        $this->dropColumn(
            '{{%company}}', 
            'company_bonus_commission'
        );
        
        $this->dropColumn(
            '{{%transfer_candidate}}', 
            'bonus_commission'
        );
    }
}
