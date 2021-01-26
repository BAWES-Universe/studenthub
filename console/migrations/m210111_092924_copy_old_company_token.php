<?php

use yii\db\Migration;

/**
 * Class m210111_092924_copy_old_toke
 */
class m210111_092924_copy_old_company_token extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $q = "insert into `contact_token` (`contact_uuid`,`token_value`,`token_device`,`token_device_id`,`token_status`, ";
        $q .= "`token_last_used_datetime`,`token_expiry_datetime`,`token_created_datetime`) ";
        $q .= "SELECT `company_contact`.`contact_uuid`,`company_token`.`token_value`,`company_token`.`token_device`, ";
        $q .= "`company_token`.`token_device_id`,`company_token`.`token_status`,`company_token`.`token_last_used_datetime`, ";
        $q .= "`company_token`.`token_expiry_datetime`,`company_token`.`token_created_datetime` FROM `company_token` left join ";
        $q .= "`company_contact` on `company_contact`.`company_id` = `company_token`.`company_id` where `company_contact`.`role` = 'Owner'";
        Yii::$app->db->createCommand($q)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210111_092924_copy_old_toke cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210111_092924_copy_old_toke cannot be reverted.\n";

        return false;
    }
    */
}
