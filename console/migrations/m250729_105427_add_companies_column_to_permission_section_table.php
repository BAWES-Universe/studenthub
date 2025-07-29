<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%permission_section}}`.
 */
class m250729_105427_add_companies_column_to_permission_section_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('permission_section', 'companies', $this->json()->null()->after('created_at'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('permission_section', 'companies');
    }
}
