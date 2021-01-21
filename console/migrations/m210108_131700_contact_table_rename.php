<?php

use yii\db\Migration;

/**
 * Class m210108_131700_contact_table_rename
 */
class m210108_131700_contact_table_rename extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameTable('company_contact_email', 'contact_email');
        $this->renameTable('company_contact_phone', 'contact_phone');

        $this->dropIndex(
            'idx-company-company_password_reset_token',
            'company'
        );

        $this->dropColumn('company', 'company_auth_key');
        $this->dropColumn('company', 'company_password_hash');
        $this->dropColumn('company', 'company_password_reset_token');

        //contact 

        $this->dropForeignKey(
            'fk-contact-CASCADE',
            'contact'
        );

        $this->dropIndex(
            'idx-company_contact-company_id',
            'contact'
        );

        $contactTableData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('contact');
        if (isset($contactTableData->foreignKeys['fk-contact-company_id'])) {

            $this->dropForeignKey(
                'fk-contact-company_id',
                'contact'
            );

            $this->dropIndex(
                'idx-contact-company_id',
                'contact'
            );
        }
        $this->dropColumn('contact', 'company_id'); 
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
        echo "m210108_131700_contact_table_rename cannot be reverted.\n";

        return false;
    }
    */
}
