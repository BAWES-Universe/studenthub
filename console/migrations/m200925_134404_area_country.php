<?php

use yii\db\Migration;

/**
 * Class m200925_134404_area_country
 */
class m200925_134404_area_country extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $this->addColumn('area', 'country_id', $this->integer(11)->notNull()->after('area_uuid'));

        $this->createIndex(
            'idx-area-country_id',
            'area',
            'country_id'
        );
        
        $this->addForeignKey(
            'fk-area-country_id',
            'area',
            'country_id',
            'country',
            'country_id'
        );
        
        $this->execute("SET foreign_key_checks = 1;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200925_134404_area_country cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200925_134404_area_country cannot be reverted.\n";

        return false;
    }
    */
}
