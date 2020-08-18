<?php

use yii\db\Migration;

/**
 * Class m200818_101859_update_company_table
 */
class m200818_101859_update_company_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%company}}', 'company_common_name_en', $this->string(255)->null()->after('company_name'));
        $this->addColumn('{{%company}}', 'company_common_name_ar', $this->string(255)->null()->after('company_common_name_en'));

        $this->addColumn('{{%company}}', 'company_description_en', $this->text()->null()->after('company_common_name_ar'));
        $this->addColumn('{{%company}}', 'company_description_ar', $this->text()->null()->after('company_description_en'));

        $this->addColumn('{{%company}}', 'company_website', $this->text()->null()->after('company_description_ar'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200818_101859_update_company_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200818_101859_update_company_table cannot be reverted.\n";

        return false;
    }
    */
}
