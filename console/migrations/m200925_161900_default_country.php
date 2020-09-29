<?php

use yii\db\Migration;

/**
 * Class m200925_161900_default_country
 */
class m200925_161900_default_country extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $kuwait = $this->db->createCommand('select * from country where country_name_en="Kuwait"')->queryOne();

        if(!$kuwait) {

            $this->db->createCommand('insert into country set country_name_en="Kuwait", country_name_ar="الكويت", 
                country_nationality_name_en="Kuwaiti", country_nationality_name_ar="كويتي"')->execute();

            $country_id = Yii::$app->db->getLastInsertID();
        } else {
            $country_id = $kuwait['country_id'];
        }

        $this->db->createCommand('UPDATE area SET country_id="' . $country_id . '"')->execute();

        $this->execute("SET foreign_key_checks = 1;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200925_161900_default_country cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200925_161900_default_country cannot be reverted.\n";

        return false;
    }
    */
}
