<?php

use yii\db\Migration;

/**
 * Class m200902_130750_admin_changes
 */
class m200902_130750_admin_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%admin}}',
            'admin_limited_access',
            $this->smallInteger()->defaultValue(0)->after('admin_status')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200902_130750_admin_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200902_130750_admin_changes cannot be reverted.\n";

        return false;
    }
    */
}
