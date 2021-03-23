<?php

use yii\db\Migration;

/**
 * Class m210222_122653_invitation
 */
class m210222_122653_invitation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('invitation', [
            'invitation_uuid' => $this->char(60),
            'candidate_id' => $this->integer (11),
            'request_uuid' => $this->char(60)->notNull(),
            'invitation_status' => $this->tinyInteger(2)->defaultValue(0)->comment('1-Invited , 2-Rejected, 3-Accepted'),
            'invitation_created_by_staff' => $this->integer(11),
            'invitation_updated_by_staff' => $this->integer(11),
            'invitation_created_by_company' => $this->integer(11),
            'invitation_updated_by_company' => $this->integer(11),
            'invitation_created_at' => $this->datetime(),
            'invitation_updated_at' => $this->datetime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'invitation', 'invitation_uuid');

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-invitation-candidate_id',
            'invitation',
            'candidate_id'
        );

        // add foreign key for table `candidate`
        $this->addForeignKey(
            'fk-invitation-candidate_id',
            'invitation',
            'candidate_id',
            'candidate',
            'candidate_id'
        );

        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-invitation-request_uuid',
            'invitation',
            'request_uuid'
        );

        // add foreign key for table `request`
        $this->addForeignKey(
            'fk-invitation-request_uuid',
            'invitation',
            'request_uuid',
            'request',
            'request_uuid'
        );

        // creates index for column `invitation_created_by_staff`
        $this->createIndex(
            'idx-invitation-invitation_created_by_staff',
            'invitation',
            'invitation_created_by_staff'
        );

        // add foreign key for table `staff`
        $this->addForeignKey(
            'fk-invitation-invitation_created_by_staff',
            'invitation',
            'invitation_created_by_staff',
            'staff',
            'staff_id'
        );

        // creates index for column `invitation_updated_by_staff`
        $this->createIndex(
            'idx-invitation-invitation_updated_by_staff',
            'invitation',
            'invitation_updated_by_staff'
        );

        // add foreign key for table `staff`
        $this->addForeignKey(
            'fk-invitation-invitation_updated_by_staff',
            'invitation',
            'invitation_updated_by_staff',
            'staff',
            'staff_id'
        );

        // creates index for column `invitation_created_by_company`
        $this->createIndex(
            'idx-invitation-invitation_created_by_company',
            'invitation',
            'invitation_created_by_company'
        );

        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-invitation-invitation_created_by_company',
            'invitation',
            'invitation_created_by_company',
            'company',
            'company_id'
        );

        // creates index for column `invitation_updated_by_company`
        $this->createIndex(
            'idx-invitation-invitation_updated_by_company',
            'invitation',
            'invitation_updated_by_company'
        );

        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-invitation-invitation_updated_by_company',
            'invitation',
            'invitation_updated_by_company',
            'company',
            'company_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable ('invitation');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210222_122653_invitation cannot be reverted.\n";

        return false;
    }
    */
}
