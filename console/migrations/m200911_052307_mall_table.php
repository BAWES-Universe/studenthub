<?php

use yii\db\Migration;

/**
 * Class m200911_052307_mall_table
 */
class m200911_052307_mall_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('mall', [
            "mall_uuid" => $this->char(60),
            "mall_name_en" => $this->string()->notNull(),
            "mall_name_ar" => $this->string()->notNull(),
            'mall_created_datetime' => $this->datetime()->notNull(),
            'mall_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'mall', 'mall_uuid');

        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        $this->addColumn('store', 'mall_uuid', $this->char(60)->after('brand_uuid')->null());

        // creates index for column `inspector_uuid`
        $this->createIndex(
            'idx-store-mall_uuid',
            'store',
            'mall_uuid'
        );
        // add foreign key for table `inspector_token`
        $this->addForeignKey(
            'fk-store-mall_uuid',
            'store',
            'mall_uuid',
            'mall',
            'mall_uuid',
            'CASCADE'
        );
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-store-mall_uuid','store');
        $this->dropColumn('store','mall_uuid');
        $this->dropTable('mall');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200911_052307_mall_table cannot be reverted.\n";

        return false;
    }
    */
}
