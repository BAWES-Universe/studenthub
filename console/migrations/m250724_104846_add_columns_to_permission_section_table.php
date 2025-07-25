<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%permission_section}}`.
 */
class m250724_104846_add_columns_to_permission_section_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('permission_section', 'is_company_specific_permission', $this->boolean()->defaultValue(0)->after('created_at'));    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('permission_section', 'is_company_specific_permission');
    }
}
