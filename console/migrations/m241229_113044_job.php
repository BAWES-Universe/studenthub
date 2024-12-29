<?php

use yii\db\Migration;

/**
 * Class m241229_113044_job
 */
class m241229_113044_job extends Migration
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

        //job

        $this->createTable('{{%job}}', [
            "job_uuid" => $this->char(60),
            "story_uuid" => $this->char(60)->notNull(),
            "request_uuid" => $this->char(60)->notNull(),
            "area_uuid" => $this->char(60),
            "position" => $this->string()->notNull(),
            "position_ar" => $this->string()->null(),
            "description" => $this->text()->null(),
            "description_ar" => $this->text()->null(),
            "hours_per_day" => $this->tinyInteger(2)->null(),
            "days_per_week" => $this->tinyInteger(1)->null(),

            //compensation
            "compensation_type" => "Enum('FIXED_PRICE', 'HOURLY', 'MONTHLY_SALARY')",
            "compensation_amount" => $this->double(10, 3)->null(),
            "compensation_description" => $this->text()->null(),
            "compensation_description_ar" => $this->text()->null(),

            //filter
            "min_age" => $this->tinyInteger(3)->null(),
            "max_age" => $this->tinyInteger(3)->null(),
            "gender" => $this->tinyInteger(1)->null()->comment("MALE = 1, FEMALE = 2, OTHER = 3"),
            "available_from" => $this->dateTime()->null(),
            "available_to" => $this->dateTime()->null(),

            "status" => $this->tinyInteger(1)->defaultValue(0)->comment("0 -DRAFT | 1 - ACTIVE | 2- CLOSED"),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
            "created_by" => $this->integer(11),
            "updated_by" => $this->integer(11),
        ], $tableOptions);

        $this->addPrimaryKey('pk-job-job_uuid',
            'job', "job_uuid");

        // creates index for column `updated_by`
        $this->createIndex(
            'idx-job-updated_by',
            'job',
            'updated_by'
        );

        // add foreign key for table `updated_by`
        $this->addForeignKey(
            'fk-job-updated_by',
            'job',
            'updated_by',
            'staff',
            'staff_id'
        );

        // creates index for column `created_by`
        $this->createIndex(
            'idx-job-created_by',
            'job',
            'created_by'
        );

        // add foreign key for table `created_by`
        $this->addForeignKey(
            'fk-job-created_by',
            'job',
            'created_by',
            'staff',
            'staff_id'
        );

        // creates index for column `area_uuid`
        $this->createIndex(
            'idx-job-area_uuid',
            'job',
            'area_uuid'
        );

        // add foreign key for table `area_uuid`
        $this->addForeignKey(
            'fk-job-area_uuid',
            'job',
            'area_uuid',
            'area',
            'area_uuid'
        );

        // creates index for column `story_uuid`
        $this->createIndex(
            'idx-job-story_uuid',
            'job',
            'story_uuid'
        );

        // add foreign key for table `story_uuid`
        $this->addForeignKey(
            'fk-job-story_uuid',
            'job',
            'story_uuid',
            'story',
            'story_uuid'
        );

        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-job-request_uuid',
            'job',
            'request_uuid'
        );

        // add foreign key for table `request_uuid`
        $this->addForeignKey(
            'fk-job-request_uuid',
            'job',
            'request_uuid',
            'request',
            'request_uuid',
            'CASCADE'
        );

        //job_skills

        $this->createTable('{{%job_skills}}', [
            "job_uuid" => $this->char(60),
            "skill" => $this->string()->notNull(),
            "skill_ar" => $this->string(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-job_skills','job_skills', ["job_uuid", "skill"]);

        $this->addForeignKey(
            'fk-job_skills-job_uuid',
            '{{%job_skills}}',
            'job_uuid',
            '{{%job}}',
            'job_uuid',
            'CASCADE');

        // job_interest

        $this->createTable('{{%job_interest}}', [
            'job_interest_uuid' => $this->char(60),
            'candidate_id' => $this->integer(11)->notNull(),
            'job_uuid' => $this->char(60)->notNull(),
            'status' => $this->string()->defaultValue('PENDING')->comment("INTERESTED | SHORTLISTED | REJECTED"),
            'notes' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')
        ], $tableOptions);

        $this->addPrimaryKey('pk-job_interest-job_uuid',
            'job_interest', "job_interest_uuid");

        // creates index for column `job_uuid`
        $this->createIndex(
            'idx-job_interest-job_uuid',
            'job_interest',
            'job_uuid'
        );

        $this->addForeignKey(
            'fk-job_interest-job_uuid',
            '{{%job_interest}}',
            'job_uuid',
            '{{%job}}',
            'job_uuid',
            'CASCADE');

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-job_interest-candidate_id',
            'job_interest',
            'candidate_id'
        );

        $this->addForeignKey(
            'fk-job_interest-candidate_id',
            '{{%job_interest}}',
            'candidate_id',
            '{{%candidate}}',
            'candidate_id',
            'CASCADE');

        //invitation

        $this->addColumn("invitation", "job_interest_uuid",
            $this->char(60)->null()->comment("invitation from job interests"));

        // creates index for column `job_interest_uuid`
        $this->createIndex(
            'idx-invitation-job_interest_uuid',
            'invitation',
            'job_interest_uuid'
        );

        // add foreign key for table `job_interest_uuid`
        $this->addForeignKey(
            'fk-invitation-job_interest_uuid',
            'invitation',
            'job_interest_uuid',
            'job_interest',
            'job_interest_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("SET foreign_key_checks = 0;");

        $this->dropTable("job_interest");
        $this->dropTable("job_skills");
        $this->dropTable("job");
        $this->dropForeignKey("fk-invitation-job_interest_uuid", "invitation");
        $this->dropIndex("idx-invitation-job_interest_uuid", "invitation");
        $this->dropColumn("invitation", "job_interest_uuid");

        $this->execute("SET foreign_key_checks = 1;");

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241229_113044_job cannot be reverted.\n";

        return false;
    }
    */
}
