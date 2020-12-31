<?php

use yii\db\Migration;

/**
 * Class m201231_105431_fix_unique_key
 */
class m201231_105431_fix_unique_key extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //candidate

        $this->dropIndex('candidate_civil_id', 'candidate');
        $this->dropIndex('candidate_civil_id_2', 'candidate');
        $this->dropIndex('candidate_password_reset_token', 'candidate');

        $this->createIndex('idx-candidate-candidate_civil_id', 'candidate', ['candidate_civil_id', 'deleted'], true);
        
        $this->createIndex('idx-candidate-candidate_password_reset_token', 'candidate', ['candidate_password_reset_token', 'deleted'], true);
         
        //company

        $this->dropIndex('company_password_reset_token', 'company');

        $this->createIndex('idx-company-company_password_reset_token', 'company', ['company_password_reset_token', 'deleted'], true);

        //inspector

        $this->dropIndex('inspector_password_reset_token', 'inspector');
        $this->dropIndex('inspector_email', 'inspector');
        
        $this->createIndex('idx-inspector-inspector_password_reset_token', 'inspector', ['inspector_password_reset_token', 'inspector_deleted'], true);
        $this->createIndex('idx-inspector-inspector_email', 'inspector', ['inspector_email', 'inspector_deleted'], true);

        //staff 

        $this->dropIndex('staff_email', 'staff');
        $this->dropIndex('staff_password_reset_token', 'staff');

        $this->createIndex('idx-staff-staff_email', 'staff', ['staff_email', 'deleted'], true);
        $this->createIndex('idx-staff-staff_password_reset_token', 'staff', ['staff_password_reset_token', 'deleted'], true);

        //transfer_candidate

        $this->dropIndex('transfer_confirmation_id', 'transfer_candidate');
        
        $this->createIndex('idx-transfer_candidate-transfer_confirmation_id', 'transfer_candidate', ['transfer_confirmation_id', 'deleted'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201231_105431_fix_unique_key cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201231_105431_fix_unique_key cannot be reverted.\n";

        return false;
    }
    */
}
