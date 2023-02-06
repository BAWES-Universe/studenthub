<?php

use yii\db\Migration;

/**
 * Class m230203_070415_staff_leaves
 */
class m230203_070415_staff_leaves extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('staff_leave','category',$this->string(225)->after('note'));
        $this->addColumn('staff_leave','file',$this->string(225)->after('category'));
        $this->addColumn('staff_leave','status',$this->tinyInteger(2)->after('file')->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('staff_leave','category');
        $this->dropColumn('staff_leave','file');
    }
}
