<?php

use yii\db\Migration;

/**
 * Class m201020_141924_duplicate_country
 */
class m201020_141924_duplicate_country extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //to column to hide duplicate countries in future

        $this->addColumn('country', 'country_from_google_map', $this->boolean()->after('country_nationality_name_ar')->defaultValue(0));

        //original added from migration

        $uae = $this->db->createCommand('SELECT * from country WHERE country_name_en="United Arab Emirates (UAE)"')->queryOne();

        $uk = $this->db->createCommand('SELECT * from country WHERE country_name_en="United Kingdom (UK)"')->queryOne();

        $usa = $this->db->createCommand('SELECT * from country WHERE country_name_en="United States of America (USA)"')->queryOne();

        //(possible) duplicate added from google map

        $duplicate_uae = $this->db->createCommand('SELECT * from country WHERE country_name_en="United Arab Emirates"')->queryOne();

        $duplicate_uk = $this->db->createCommand('SELECT * from country WHERE country_name_en="United Kingdom"')->queryOne();

        $duplicate_usa = $this->db->createCommand('SELECT * from country WHERE country_name_en="United States of America"')->queryOne();

        //mark as from google, countries not added from migration

        $this->db->createCommand('UPDATE country SET country_from_google_map = 1 WHERE country_id > 184')->execute();

        //use original country data in nationality

        if($uae && $duplicate_uae)
            $this->db->createCommand('UPDATE candidate SET country_id = '. $uae['country_id'].' WHERE country_id = '. $duplicate_uae['country_id'])->execute();

        if($uk && $duplicate_uk)
            $this->db->createCommand('UPDATE candidate SET country_id = '. $uk['country_id'].' WHERE country_id = '. $duplicate_uk['country_id'])->execute();

        if($usa && $duplicate_usa)
            $this->db->createCommand('UPDATE candidate SET country_id = '. $usa['country_id'].' WHERE country_id = '. $duplicate_usa['country_id'])->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201020_141924_duplicate_country cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201020_141924_duplicate_country cannot be reverted.\n";

        return false;
    }
    */
}
