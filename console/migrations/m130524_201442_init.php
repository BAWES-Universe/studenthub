<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
       // $this->db->createCommand("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))")
       //     ->execute();

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Owner
        $this->createTable('admin', [
            'admin_id' => $this->primaryKey(),
            'admin_name' => $this->string()->notNull(),
            'admin_email' => $this->string()->notNull()->unique(),
            'admin_auth_key' => $this->string(32)->notNull(),
            'admin_password_hash' => $this->string()->notNull(),
            'admin_password_reset_token' => $this->string()->unique(),
            'admin_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'admin_created_at' => $this->datetime()->notNull(),
            'admin_updated_at' => $this->datetime()->notNull(),
        ], $tableOptions);

        // Staff working for Studenthub
        $this->createTable('staff', [
            'staff_id' => $this->primaryKey(),
            'staff_name' => $this->string()->notNull(),
            'staff_email' => $this->string()->notNull()->unique(),
            'staff_auth_key' => $this->string(32)->notNull(),
            'staff_password_hash' => $this->string()->notNull(),
            'staff_password_reset_token' => $this->string()->unique(),
            'staff_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'staff_created_at' => $this->datetime()->notNull(),
            'staff_updated_at' => $this->datetime()->notNull(),
        ], $tableOptions);

        // Company who will recruit
        $this->createTable('company', [
            'company_id' => $this->primaryKey(),
            'company_name' => $this->string()->notNull(),
            'company_email' => $this->string()->notNull()->unique(),
            'company_auth_key' => $this->string(32)->notNull(),
            'company_password_hash' => $this->string()->notNull(),
            'company_password_reset_token' => $this->string()->unique(),
            'company_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'company_created_at' => $this->datetime()->notNull(),
            'company_updated_at' => $this->datetime()->notNull(),
        ], $tableOptions);

        // Candidates added by staff
        $this->createTable('candidate', [
            'candidate_id' => $this->primaryKey(),
            'company_id' => $this->integer(),
            'candidate_name' => $this->string()->notNull(),
            'candidate_email' => $this->string()->notNull()->unique(),
            'candidate_civil_id' => $this->string()->notNull()->unique(),
            'candidate_auth_key' => $this->string(32)->notNull(),
            'candidate_password_hash' => $this->string()->notNull(),
            'candidate_password_reset_token' => $this->string()->unique(),
            'candidate_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'deleted' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'candidate_created_at' => $this->datetime()->notNull(),
            'candidate_updated_at' => $this->datetime()->notNull(),
        ], $tableOptions);

        // creates index for column `company_id`
        $this->createIndex(
            'idx-candidate-company_id',
            'candidate',
            'company_id'
        );
        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-candidate-company_id',
            'candidate',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

    }

    public function down()
    {
        // drops foreign key for table `user`
        $this->dropForeignKey(
            'fk-candidate-company_id',
            'candidate'
        );

        // drops index for column `company_id`
        $this->dropIndex(
            'idx-candidate-company_id',
            'candidate'
        );
        $this->dropTable('candidate');
        $this->dropTable('company');
        $this->dropTable('staff');
        $this->dropTable('admin');
    }
}
