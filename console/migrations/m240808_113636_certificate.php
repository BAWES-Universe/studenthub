<?php

use yii\db\Migration;

/**
 * Class m240808_113636_certificate
 */
class m240808_113636_certificate extends Migration
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

        $this->createTable('{{%exam}}', [
            "exam_uuid" => $this->char(60),
            "title_en" => $this->string()->notNull(),
            "title_ar" => $this->string()->null(),
            "description_en" => $this->string()->null(),
            "description_ar" => $this->string()->null(),

            "staff_id" => $this->integer(11)->null(),
            "is_deleted" => $this->boolean()->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'exam', 'exam_uuid');

        $this->createTable('{{%exam_question}}', [
            "question_uuid" => $this->char(60),
            "exam_uuid" => $this->char(60),

            "question_type" => $this->tinyInteger()->comment("checkbox radio text file boolean number etc"),
            "question_en" => $this->string()->notNull(),
            "question_ar" => $this->string()->null(),

            "question_file_extensions" => $this->string()->null(),
            "question_file_maxsize" => $this->integer(11)->null(),

            "staff_id" => $this->integer(11)->null(),
            "question_sort_order" => $this->integer(6),
            "is_deleted" => $this->boolean()->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'exam_question', 'question_uuid');

        //staff_id

        $this->createIndex(
            'idx-exam_question-staff_id', 'exam_question', 'staff_id'
        );

        $this->addForeignKey(
            'fk-exam_question-staff_id', 'exam_question', 'staff_id',
            'staff', 'staff_id'
        );

        //exam_uuid

        $this->createIndex(
            'idx-exam_question-exam_uuid', 'exam_question', 'exam_uuid'
        );

        $this->addForeignKey(
            'fk-exam_question-exam_uuid', 'exam_question', 'exam_uuid',
            'exam', 'exam_uuid'
        );

        $this->createTable('{{%exam_question_choice}}', [
            "choice_uuid" => $this->char(60),
            "question_uuid" => $this->char(60),
            "choice_value_en" => $this->string()->notNull(),
            "choice_value_ar" => $this->string()->null(),
            "choice_sort_order" => $this->integer(11),
            "is_deleted" => $this->boolean()->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'exam_question_choice', 'choice_uuid');

        //question_uuid

        $this->createIndex(
            'idx-exam_question_choice-question_uuid', 'exam_question_choice', 'question_uuid'
        );

        $this->addForeignKey(
            'fk-exam_question_choice-question_uuid', 'exam_question_choice', 'question_uuid',
            'exam_question', 'question_uuid'
        );
 
        $this->createTable('{{%exam_question_answer}}', [
            "answer_uuid" => $this->char(60),
            "exam_uuid" => $this->char(60),
            "candidate_id" => $this->integer(11)->notNull(),
            "question_uuid" => $this->char(60),
            "question_type" => $this->tinyInteger(),
            "question_en" => $this->string()->notNull(),
            "question_ar" => $this->string()->null(),
            "answer" => $this->string()->null(),
            "is_deleted" => $this->boolean()->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'exam_question_answer', 'answer_uuid');

        //question_uuid

        $this->createIndex(
            'idx-exam_question_answer-question_uuid', 'exam_question_answer', 'question_uuid'
        );

        $this->addForeignKey(
            'fk-exam_question_answer-question_uuid', 'exam_question_answer', 'question_uuid',
            'exam_question', 'question_uuid'
        );

        //exam_uuid

        $this->createIndex(
            'idx-exam_question_answer-exam_uuid', 'exam_question_answer', 'exam_uuid'
        );

        $this->addForeignKey(
            'fk-exam_question_answer-exam_uuid', 'exam_question_answer', 'exam_uuid',
            'exam', 'exam_uuid'
        );

        //candidate_id

        $this->createIndex(
            'idx-exam_question_answer-candidate_id', 'exam_question_answer', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-exam_question_answer-candidate_id', 'exam_question_answer', 'candidate_id',
            'candidate', 'candidate_id'
        );

        $this->createTable('{{%candidate_certificate}}', [
            "certificate_uuid" => $this->char(60),
            "certificate_type" => $this->tinyInteger(1)->defaultValue(0),
            "candidate_id" => $this->integer(11)->notNull(),

            "candidate_work_history_id" => $this->integer(11)->null(),
            "exam_uuid" => $this->char(60)->null(),

            "store_id" => $this->integer(11)->null(),
            "company_id" => $this->integer(11)->null(),
            "parent_company_id" => $this->integer(11)->null(),

            "start_date" => $this->date()->null(),
            "end_date" => $this->date()->null(),

            "staff_id" => $this->integer(11)->null(),
            "is_deleted" => $this->boolean()->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_certificate', 'certificate_uuid');

        //staff_id

        $this->createIndex(
            'idx-candidate_certificate-staff_id', 'candidate_certificate', 'staff_id'
        );

        $this->addForeignKey(
            'fk-candidate_certificate-staff_id', 'candidate_certificate', 'staff_id',
            'staff', 'staff_id'
        );
        
        //candidate_id

        $this->createIndex(
            'idx-candidate_certificate-candidate_id', 'candidate_certificate', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-candidate_certificate-candidate_id', 'candidate_certificate', 'candidate_id',
            'candidate', 'candidate_id'
        );

        //candidate_work_history_id

         $this->createIndex(
             'idx-candidate_certificate-candidate_work_history_id', 'candidate_certificate', 'candidate_work_history_id'
         );

        $this->addForeignKey(
            'fk-candidate_certificate-candidate_work_history_id', 'candidate_certificate', 'candidate_work_history_id',
            'candidate_work_history', 'id'
        );

         //exam_uuid

         $this->createIndex(
             'idx-candidate_certificate-exam_uuid', 'candidate_certificate', 'exam_uuid'
         );

        $this->addForeignKey(
            'fk-candidate_certificate-exam_uuid', 'candidate_certificate', 'exam_uuid',
            'exam', 'exam_uuid'
        );

        //store_id

         $this->createIndex(
             'idx-candidate_certificate-store_id', 'candidate_certificate', 'store_id'
         );

        $this->addForeignKey(
            'fk-candidate_certificate-store_id', 'candidate_certificate', 'store_id',
            'store', 'store_id'
        );

        //company_id

         $this->createIndex(
             'idx-candidate_certificate-company_id', 'candidate_certificate', 'company_id'
         );

        $this->addForeignKey(
            'fk-candidate_certificate-company_id', 'candidate_certificate', 'company_id',
            'company', 'company_id'
        );

        //parent_company_id

         $this->createIndex(
             'idx-candidate_certificate-parent_company_id', 'candidate_certificate', 'parent_company_id'
         );

        $this->addForeignKey(
            'fk-candidate_certificate-parent_company_id', 'candidate_certificate', 'parent_company_id',
            'company', 'company_id'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240808_113636_certificate cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240808_113636_certificate cannot be reverted.\n";

        return false;
    }
    */
}
