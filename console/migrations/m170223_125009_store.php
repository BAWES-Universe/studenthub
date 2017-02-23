<?php

use yii\db\Migration;

class m170223_125009_store extends Migration
{
    public function up()
    {
        $this->createTable('store', [
            'store_id' => $this->primaryKey(),
            'company_id' => $this->integer(),
            'store_name' => $this->string()->notNull(),
            'store_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'store_created_at' => $this->datetime()->notNull(),
            'store_updated_at' => $this->datetime()->notNull()
        ]);

        $this->createIndex(
            'idx-store-company_id',
            'store',
            'company_id'
        );
        
        $this->addForeignKey(
            'fk-store-company_id',
            'store',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey(
            'fk-store-company_id',
            'store'
        );
        
        $this->dropIndex(
            'idx-store-company_id',
            'store'
        );

        $this->dropTable('store');
    }
}
