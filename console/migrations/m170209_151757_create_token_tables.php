<?php

use yii\db\Migration;

class m170209_151757_create_token_tables extends Migration
{
    public function safeUp()
    {
        /**
         * Admin Tokens
         */
        $this->createTable('admin_token', [
            'token_id' => $this->primaryKey(),
            'admin_id' => $this->integer()->notNull(),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ]);
        // creates index for column `admin_id`
        $this->createIndex(
            'idx-admin_token-admin_id',
            'admin_token',
            'admin_id'
        );
        // add foreign key for table `admin`
        $this->addForeignKey(
            'fk-admin_token-admin_id',
            'admin_token',
            'admin_id',
            'admin',
            'admin_id',
            'CASCADE'
        );

        /**
         * Candidate Tokens
         */
        $this->createTable('candidate_token', [
            'token_id' => $this->primaryKey(),
            'candidate_id' => $this->integer()->notNull(),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ]);
        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-candidate_token-candidate_id',
            'candidate_token',
            'candidate_id'
        );
        // add foreign key for table `candidate`
        $this->addForeignKey(
            'fk-candidate_token-candidate_id',
            'candidate_token',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );

        /**
         * Company Tokens
         */
        $this->createTable('company_token', [
            'token_id' => $this->primaryKey(),
            'company_id' => $this->integer()->notNull(),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ]);
        // creates index for column `company_id`
        $this->createIndex(
            'idx-company_token-company_id',
            'company_token',
            'company_id'
        );
        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-company_token-company_id',
            'company_token',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        /**
         * Staff Tokens
         */
        $this->createTable('staff_token', [
            'token_id' => $this->primaryKey(),
            'staff_id' => $this->integer()->notNull(),
            'token_value' => $this->string()->notNull(),
            'token_device' => $this->string(),
            'token_device_id' => $this->string(),
            'token_status' => $this->smallInteger()->notNull(),
            'token_last_used_datetime' => $this->datetime(),
            'token_expiry_datetime' => $this->datetime(),
            'token_created_datetime' => $this->datetime()->notNull(),
        ]);
        // creates index for column `staff_id`
        $this->createIndex(
            'idx-staff_token-staff_id',
            'staff_token',
            'staff_id'
        );
        // add foreign key for table `staff`
        $this->addForeignKey(
            'fk-staff_token-staff_id',
            'staff_token',
            'staff_id',
            'staff',
            'staff_id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        /**
         * Destroy Admin Tokens
         */
        // drops foreign key for table `admin`
        $this->dropForeignKey(
            'fk-admin_token-admin_id',
            'admin_token'
        );
        // drops index for column `admin_id`
        $this->dropIndex(
            'idx-admin_token-admin_id',
            'admin_token'
        );
        $this->dropTable('admin_token');

        /**
         * Destroy Candidate Tokens
         */
        // drops foreign key for table `candidate`
        $this->dropForeignKey(
            'fk-candidate_token-candidate_id',
            'candidate_token'
        );
        // drops index for column `candidate_id`
        $this->dropIndex(
            'idx-candidate_token-candidate_id',
            'candidate_token'
        );
        $this->dropTable('candidate_token');

        /**
         * Destroy Company Tokens
         */
        // drops foreign key for table `company`
        $this->dropForeignKey(
            'fk-company_token-company_id',
            'company_token'
        );
        // drops index for column `company_id`
        $this->dropIndex(
            'idx-company_token-company_id',
            'company_token'
        );
        $this->dropTable('company_token');

        /**
         * Destroy Staff Tokens
         */
        // drops foreign key for table `staff`
        $this->dropForeignKey(
            'fk-staff_token-staff_id',
            'staff_token'
        );
        // drops index for column `staff_id`
        $this->dropIndex(
            'idx-staff_token-staff_id',
            'staff_token'
        );
        $this->dropTable('staff_token');
    }
}
