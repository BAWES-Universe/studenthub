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

            $queryAll = Yii::$app->db->createCommand('SELECT * FROM `contact` left join `company` on `contact`.`company_id` = `company`.`company_id` group by `contact`.`company_id`')->queryAll();
            foreach($queryAll as $contact) {
                $q = "update `contact` set `contact_auth_key`='".$contact['company_auth_key']."', `contact_email`='".$contact['company_email']."', ";
                $q .= "`contact_password_hash`='".$contact['company_password_hash']."' where `contact`.`contact_uuid`='".$contact['contact_uuid']."'";
                Yii::$app->db->createCommand($q)->execute();
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
