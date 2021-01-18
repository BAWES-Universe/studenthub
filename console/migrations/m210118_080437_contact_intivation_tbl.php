<?php

use yii\db\Migration;

/**
 * Class m210118_080437_contact_intivation_tbl
 */
class m210118_080437_contact_intivation_tbl extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contact_invitation}}', [
            'contact_invitation_uuid' => $this->char(60),
            'contact_uuid' => $this->char(60),
            'company_id' => $this->integer(),
            'email_to_invite' => $this->string()->notNull(),
            'otp' => $this->string(60)->unique(),
            'accepted' => $this->boolean(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'contact_invitation', 'contact_invitation_uuid');

        $this->createIndex(
            'idx-contact_invitation-contact_uuid',
            'contact_invitation',
            'contact_uuid'
        );

        $this->addForeignKey(
            'fk-contact_invitation-contact_uuid',
            'contact_invitation',
            'contact_uuid',
            'contact',
            'contact_uuid',
            'SET NULL'
        );

        $this->createIndex(
            'idx-contact_invitation-company_id',
            'contact_invitation',
            'company_id'
        );

        $this->addForeignKey(
            'fk-contact_invitation-company_id',
            'contact_invitation',
            'company_id',
            'company',
            'company_id',
            'SET NULL'
        );
    }
}
