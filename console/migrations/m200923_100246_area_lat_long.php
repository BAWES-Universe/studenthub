<?php

use yii\db\Migration;

/**
 * Class m200923_100246_area_lat_long
 */
class m200923_100246_area_lat_long extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('area', 'area_latitude', $this->decimal(9,6)->after('area_name_ar'));
        $this->addColumn('area', 'area_longitude', $this->decimal(9,6)->after('area_latitude'));

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200923_100246_area_lat_long cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200923_100246_area_lat_long cannot be reverted.\n";

        return false;
    }
    */
}
