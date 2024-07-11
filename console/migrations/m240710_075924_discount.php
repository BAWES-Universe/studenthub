<?php

use yii\db\Migration;

/**
 * Class m240710_075924_discount
 */
class m240710_075924_discount extends Migration
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

        $this->createTable('{{%discount_category}}', [
            "category_id" => $this->primaryKey(11),
            'name_en' => $this->string()->notNull(),
            "name_ar" => $this->string(),
            "image" => $this->string(),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->createTable('{{%discount}}', [
            "discount_uuid" => $this->char(60),
            "category_id" => $this->integer(11)->notNull(),
            "company_id" => $this->integer(11)->notNull(),
            "store_id" => $this->integer(11),
            "description_en" => $this->text()->notNull(),
            "description_ar" => $this->text()->notNull(),
            "how_to_apply_en" => $this->text(),
            "how_to_apply_ar" => $this->text(),
            "image" => $this->string(),
            "valid_until" => $this->dateTime(),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'discount', 'discount_uuid');

        //category_id

        $this->createIndex(
            'idx-discount-category_id', 'discount', 'category_id'
        );

        $this->addForeignKey(
            'fk-discount-category_id', 'discount', 'category_id',
            'discount_category', 'category_id'
        );

        //company_id

        $this->createIndex(
            'idx-discount-company_id', 'discount', 'company_id'
        );

        $this->addForeignKey(
            'fk-discount-company_id', 'discount', 'company_id',
            'company', 'company_id'
        );

        //store_id

        $this->createIndex(
            'idx-discount-store_id', 'discount', 'store_id'
        );

        $this->addForeignKey(
            'fk-discount-store_id', 'discount', 'store_id',
            'store', 'store_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240710_075924_discount cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240710_075924_discount cannot be reverted.\n";

        return false;
    }
    */
}
