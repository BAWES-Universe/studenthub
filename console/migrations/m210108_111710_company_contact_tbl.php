<?php

use yii\db\Migration;

/**
 * Class m210108_111710_company_contact_tbl
 */
class m210108_111710_company_contact_tbl extends Migration
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

        $this->createTable('company_contact', [
            "company_contact_uuid" => $this->char(60),
            "contact_uuid" => $this->char(60),
            'company_id' => $this->integer(),
            'role' => $this->string()->notNull()->defaultValue("Owner")->comment('Owner,HR,Finance,Other'),
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull(),
            'created_by' => $this->char(60),
            'updated_by' => $this->char(60),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'company_contact', 'company_contact_uuid');

        // creates index for column `company_id`
        $this->createIndex(
            'idx-company_contact-company_id',
            'company_contact',
            'company_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-company_contact-company_id',
            'company_contact',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-company_contact-contact_uuid',
            'company_contact',
            'contact_uuid'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-company_contact-contact_uuid',
            'company_contact',
            'contact_uuid',
            'contact',
            'contact_uuid',
            'CASCADE'
        );

        // adding contact details of those who don't have contact details
        $query = Yii::$app->db->createCommand('SELECT * FROM `contact`')->queryAll();
        foreach($query as $contact) {
            $uuid = Yii::$app->db->createCommand("select CONCAT('comp_cont_',uuid())")->queryScalar();
            $role = ($contact['contact_email'])? 'Owner' : 'Other';
            $companyQuery = "INSERT INTO company_contact SET 
                        company_contact_uuid='".$uuid."',
                        contact_uuid='".$contact['contact_uuid']."',
                        company_id='".$contact['company_id']."',
                        role='".$role."',
                        created_at='".$contact['contact_created_at']."',
                        updated_at='".$contact['contact_updated_at']."',
                        created_by='".$contact['company_id']."',
                        updated_by='".$contact['company_id']."'";
            Yii::$app->db->createCommand($companyQuery)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210108_111710_company_contact_tbl cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210108_111710_company_contact_tbl cannot be reverted.\n";

        return false;
    }
    */
}
