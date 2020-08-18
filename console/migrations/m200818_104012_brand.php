<?php

use yii\db\Migration;

/**
 * Class m200818_104012_brand
 */
class m200818_104012_brand extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        $this->createTable('brand', [
            "brand_uuid" => $this->char(60),
            'company_id' => $this->integer(),
            'brand_name_en' => $this->string()->notNull(),
            'brand_name_ar' => $this->string()->notNull(),
            'brand_logo' => $this->string(),
            'brand_created_datetime' => $this->datetime()->notNull(),
            'brand_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'brand', 'brand_uuid');
        
        // creates index for column `company_id`
        $this->createIndex(
            'idx-brand-company_id',
            'brand',
            'company_id'
        );
        
        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-brand-CASCADE',
            'brand',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200818_104012_brand cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200818_104012_brand cannot be reverted.\n";

        return false;
    }
    */
}
