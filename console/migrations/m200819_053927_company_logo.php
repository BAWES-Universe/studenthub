<?php

use yii\db\Migration;

/**
 * Class m200819_053927_company_logo
 */
class m200819_053927_company_logo extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%company}}', 'company_logo',$this->string()->null()->after('company_email'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%company}}', 'company_logo');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200819_053927_company_logo cannot be reverted.\n";

        return false;
    }
    */
}
