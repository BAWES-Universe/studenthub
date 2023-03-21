<?php

use yii\db\Migration;

/**
 * Class m230317_123704_staff_image
 */
class m230317_123704_staff_image extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('staff','staff_photo',$this->string(225)->after('staff_salary_currency'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('staff','staff_photo');
    }
}
