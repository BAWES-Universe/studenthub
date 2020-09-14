<?php

use yii\db\Migration;

/**
 * Class m200909_110549_alter_store_table_for_brand
 */
class m200909_110549_alter_store_table_for_brand extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('ALTER TABLE store CHARACTER SET utf8 COLLATE utf8_unicode_ci;')->execute();
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        $this->addColumn('store', 'brand_uuid', $this->char(60)->after('company_id')->null());

        // creates index for column `inspector_uuid`
        $this->createIndex(
            'idx-store-brand_uuid',
            'store',
            'brand_uuid'
        );
        // add foreign key for table `inspector_token`
        $this->addForeignKey(
            'fk-store-brand_uuid',
            'store',
            'brand_uuid',
            'brand',
            'brand_uuid',
            'CASCADE'
        );

//        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
//        $this->dropForeignKey('fk-store-brand_uuid','store');
//        $this->dropColumn('store', 'brand_uuid');
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200909_110549_alter_store_table_for_brand cannot be reverted.\n";

        return false;
    }
    */
}
