<?php

use yii\db\Migration;

/**
 * Class m240630_171036_hitmap
 */
class m240630_171036_hitmap extends Migration
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

        $this->createTable('{{%firing_hitmap}}', [
            'fh_uuid' => $this->char(60),
            'company_id' => $this->integer(11)->notNull(),
            "firing_month" => $this->tinyInteger(2)->notNull(),
            "firing_year" => $this->smallInteger(4)->notNull(),
            "total" => $this->integer(11)->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'firing_hitmap', 'fh_uuid');

        //company_id

        $this->createIndex(
            'idx-firing_hitmap-company_id', 'firing_hitmap', 'company_id'
        );

        $this->addForeignKey(
            'fk-firing_hitmap-company_id', 'firing_hitmap', 'company_id', 'company', 'company_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240630_171036_hitmap cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240630_171036_hitmap cannot be reverted.\n";

        return false;
    }
    */
}
