<?php

use yii\db\Migration;

/**
 * Class m240103_101148_contact
 */
class m240103_101148_contact extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('contact', 'contact_email_verified_by',
            $this->integer(11)->null()->after('contact_email_verification')
        );

        $this->createIndex('ind-contact-contact_email_verified_by', 'contact', 'contact_email_verified_by');

        $this->addForeignKey(
            'fk-contact-contact_email_verified_by', 'contact',
            'contact_email_verified_by', 'staff', 'staff_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240103_101148_contact cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240103_101148_contact cannot be reverted.\n";

        return false;
    }
    */
}
