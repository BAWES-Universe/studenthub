<?php

use yii\db\Migration;

/**
 * Class m201005_135449_store_manager
 */
class m201005_135449_store_manager extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('store', 'store_manager_uuid', $this->char(60)->after('company_id'));
        
        $this->createIndex(
            'idx-store-store_manager_uuid',
            'store',
            'store_manager_uuid'
        );
        
        $this->addForeignKey(
            'fk-store-store_manager_uuid',
            'store',
            'store_manager_uuid',
            'company_contact',
            'contact_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201005_135449_store_manager cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201005_135449_store_manager cannot be reverted.\n";

        return false;
    }
    */
}
