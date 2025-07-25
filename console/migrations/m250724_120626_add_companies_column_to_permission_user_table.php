<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%permission_user}}`.
 */
class m250724_120626_add_companies_column_to_permission_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('permission_user', 'companies', $this->json()->null()->after('created_at'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('permission_user', 'companies');

    }
}
