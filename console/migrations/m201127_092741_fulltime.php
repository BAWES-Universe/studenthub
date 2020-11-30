<?php

use yii\db\Migration;

/**
 * Class m201127_092741_fulltime
 */
class m201127_092741_fulltime extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('fulltimer', [
            "fulltimer_uuid" => $this->char(60),
            'nationality_id' => $this->integer(11),
            'country_id' => $this->integer(11),
            "fulltimer_area_uuid" => $this->char(60),
            "fulltimer_latitude" => $this->decimal (9, 6),
            "fulltimer_longitude" => $this->decimal (9, 6),
            'fulltimer_name' => $this->string()->notNull(),
            'fulltimer_phone' => $this->string(),
            'fulltimer_email' => $this->string()->notNull()->unique(),
            'fulltimer_pdf_cv' => $this->string(),
            'fulltimer_created_datetime' => $this->datetime()->notNull(),
            'fulltimer_updated_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'fulltimer', 'fulltimer_uuid');

        // creates index for column `country_id`
        $this->createIndex(
            'idx-fulltimer-country_id',
            'fulltimer',
            'country_id'
        );

        // add foreign key for table `country_id`
        $this->addForeignKey(
            'fk-fulltimer-country_id',
            'fulltimer',
            'country_id',
            'country',
            'country_id'
        );

        // creates index for column `fulltimer_area_uuid`
        $this->createIndex(
            'idx-fulltimer-fulltimer_area_uuid',
            'fulltimer',
            'fulltimer_area_uuid'
        );

        // add foreign key for table `fulltimer_area_uuid`
        $this->addForeignKey(
            'fk-fulltimer-fulltimer_area_uuid',
            'fulltimer',
            'fulltimer_area_uuid',
            'area',
            'area_uuid'
        );

        // creates index for column `nationality_id`
        $this->createIndex(
            'idx-fulltimer-nationality_id',
            'fulltimer',
            'nationality_id'
        );

        // add foreign key for table `nationality_id`
        $this->addForeignKey(
            'fk-fulltimer-nationality_id',
            'fulltimer',
            'nationality_id',
            'country',
            'country_id',
            'CASCADE'
        );

        $this->createTable('fulltimer_tags', [
            "fulltimer_tags_id" => $this->primaryKey(),
            "fulltimer_uuid" => $this->char(60)->notNull (),
            "tag" => $this->string()
        ], $tableOptions);

        // creates index for column `fulltimer_uuid`
        $this->createIndex(
            'idx-fulltimer_tags-fulltimer_uuid',
            'fulltimer_tags',
            'fulltimer_uuid'
        );

        // add foreign key for table `fulltimer_uuid`
        $this->addForeignKey(
            'fk-fulltimer_tags-fulltimer_uuid',
            'fulltimer_tags',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid'
        );

        $this->execute("SET foreign_key_checks = 1;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201127_092741_fulltime cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201127_092741_fulltime cannot be reverted.\n";

        return false;
    }
    */
}
