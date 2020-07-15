<?php

use yii\db\Migration;

/**
 * Class m200715_082751_bank_iban
 */
class m200715_082751_bank_iban extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('bank', 'bank_iban_code', $this->string(64)->after('bank_name')->notNull());
        
        $arrIban = [
            "BKME" => "BKMEKWKW",
            "NBOK" => "NBOKKWKW",
            "COMB" => "COMBKWKW",
            "GULB" => "GULBKWKW", 
            "ABKK" => "ABKKKWKW",
            "KWIB" => "KWIBKWKW",
            "BRGN" => "BRGNKWKW",
            "KFHO" => "KFHOKWKW",
            "BBYN" => "BBYNKWKW",
            "WRBA" => "WRBAKWKW",
            "DOHB" => "DOHBKWKW"
        ];
         
        foreach($arrIban as $iban => $swift) {
            $sql = 'UPDATE bank SET bank_iban_code="'.$iban.'" WHERE bank_swift_code="'. $swift .'"';
            Yii::$app->db->createCommand($sql)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200715_082751_bank_iban cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200715_082751_bank_iban cannot be reverted.\n";

        return false;
    }
    */
}
