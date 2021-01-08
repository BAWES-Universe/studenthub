<?php

use yii\db\Migration;

/**
 * Class m210108_092910_rename_company_contact_to_contact
 */
class m210108_092910_rename_company_contact_to_contact extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameTable('company_contact','contact');
        
        $this->addColumn('contact','contact_email',$this->string()->null()->unique()->after('contact_position'));
        $this->addColumn('contact','contact_password_hash',$this->string()->null()->after('contact_email'));
        $this->addColumn('contact','contact_receive_email',$this->boolean()->defaultValue(true)->after('contact_password_hash'));
        $this->addColumn('contact','contact_receive_notification',$this->boolean()->defaultValue(true)->after('contact_receive_email'));
        $this->addColumn('contact','contact_auth_key',$this->string(32)->null()->after('contact_receive_email'));
        $this->addColumn('contact','contact_password_reset_token',$this->string()->unique()->null()->after('contact_auth_key'));
        $this->renameColumn('contact','contact_created_datetime','contact_created_at');
        $this->renameColumn('contact','contact_updated_datetime','contact_updated_at');


//         add foreign key for table `company_id`
        $this->dropForeignKey(
            'fk-company_contact-CASCADE',
            'contact'
        );

        $this->dropForeignKey(
            'fk-company_contact_phone-CASCADE',
            'company_contact_phone'
        );

        // creates index for column `contact_uuid`
        $this->dropForeignKey(
            'fk-company_contact_email-CASCADE',
            'company_contact_email'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-contact-company_id',
            'contact',
            'company_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-contact-CASCADE',
            'contact',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        // add foreign key for table `contact_uuid`
        $this->addForeignKey(
            'fk-company_contact_phone-CASCADE',
            'company_contact_phone',
            'contact_uuid',
            'contact',
            'contact_uuid',
            'CASCADE'
        );

        // add foreign key for table `contact_uuid`
        $this->addForeignKey(
            'fk-company_contact_email-CASCADE',
            'company_contact_email',
            'contact_uuid',
            'contact',
            'contact_uuid',
            'CASCADE'
        );

//         adding contact detail for those who have contact details
        $queryAll = Yii::$app->db->createCommand('SELECT * FROM `contact` left join `company` on `contact`.`company_id` = `company`.`company_id` group by `contact`.`company_id`')->queryAll();

        foreach($queryAll as $contact) {
            $q = "update `contact` set `contact_auth_key`='".$contact['company_auth_key']."', `contact_email`='".$contact['company_email']."', ";
            $q .= "`contact_password_hash`='".$contact['company_password_hash']."' where `contact`.`contact_uuid`='".$contact['contact_uuid']."'";
            Yii::$app->db->createCommand($q)->execute();
        }

        Yii::$app->db->createCommand("UPDATE `contact` SET contact_email = CONCAT('deleted_', contact_email) where company_id in (select company_id from company where deleted = 1)")->execute();

        // adding contact details of those who don't have contact details

        $companyQueryAll = Yii::$app->db->createCommand('SELECT * FROM `company` where `company_id` NOT IN (select `company_id` from `contact` GROUP by `contact`.`company_id`) and parent_company_id is null and deleted = 0')->queryAll();

        foreach($companyQueryAll as $company) {
            $uuid = Yii::$app->db->createCommand("select CONCAT('contact_',uuid())")->queryScalar();

            $companyQuery = "INSERT INTO contact SET 
                        contact_uuid='".$uuid."',
                        company_id='".$company['company_id']."',
                        contact_position='Owner',
                        contact_name='".$company['company_name']."',
                        contact_email='".$company['company_email']."',
                        contact_password_hash='".$company['company_password_hash']."',
                        contact_receive_email=1,
                        contact_auth_key='".$company['company_auth_key']."',
                        contact_receive_notification=1,
                        contact_created_at='".$company['company_created_at']."',
                        contact_updated_at='".$company['company_updated_at']."'";
            Yii::$app->db->createCommand($companyQuery)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210108_092910_rename_company_contact_to_contact cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210108_092910_rename_company_contact_to_contact cannot be reverted.\n";

        return false;
    }
    */
}
