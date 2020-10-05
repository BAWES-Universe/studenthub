<?php

use yii\db\Migration;

/**
 * Class m201005_122914_country
 */
class m201005_122914_country extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $bahrain = $this->db->createCommand("select * from country where country_name_en='Bahrain'")->queryOne();
        $uae = $this->db->createCommand("select * from country where country_name_en='United Arab Emirates'")->queryOne();
        $ksa = $this->db->createCommand("select * from country where country_name_en='Saudi Arabia'")->queryOne();
        $qatar = $this->db->createCommand("select * from country where country_name_en='Qatar'")->queryOne();
        
        if(!$bahrain) {
            $this->db->createCommand('insert into country set country_name_en="Bahrain", 
                country_name_ar="البحرين", country_nationality_name_en="Bahraini", country_nationality_name_ar="بحريني"')->execute();                    
        }
        
        if(!$uae) {
            $this->db->createCommand('insert into country set country_name_en="United Arab Emirates", 
                country_name_ar="الإمارات العربية المتحدة", country_nationality_name_en="Emirati", country_nationality_name_ar="الإمارات"')->execute();                    
        }
        
        if(!$ksa) {
            $this->db->createCommand('insert into country set country_name_en="Saudi Arabia", 
                country_name_ar="المملكة العربية السعودية", country_nationality_name_en="Saudi Arabian", country_nationality_name_ar="سعودي"')->execute();                    
        }
        
        if(!$qatar) {
            $this->db->createCommand('insert into country set country_name_en="Qatar", 
                country_name_ar="دولة قطر", country_nationality_name_en="Qatari", country_nationality_name_ar="قطري"')->execute();                    
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201005_122914_country cannot be reverted.\n";

        return false;
    }
    */
}
