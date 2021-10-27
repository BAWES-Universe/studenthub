<?php

use yii\db\Migration;

/**
 * Class m211019_093501_staff_role
 */
class m211019_093501_staff_role extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn (
            'staff',
            'staff_role',
             $this->tinyInteger(1)
                ->defaultValue(1)
                ->after ('staff_password_reset_token')
        );

    }
}

