<?php

use yii\db\Migration;

/**
 * Class m240618_144001_candidate_work_log
 */
class m240618_144001_candidate_work_log extends Migration
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

        $this->createTable('{{%candidate_work_log_feedback}}', [
            'cwlf_uuid' => $this->char(60),
            'candidate_id' => $this->integer(11)->notNull(),
            'store_id' => $this->integer(11)->notNull(),
            'company_id' => $this->integer(11)->notNull(),
            'date' => $this->date()->notNull(),
            'status' => $this->smallInteger(1)->defaultValue(1),
            'note' => $this->text(),
            'reason' => $this->string(),
            'is_public' => $this->boolean()->defaultValue(false),
            'rating' => $this->tinyInteger(1)->null(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_work_log_feedback', 'cwlf_uuid');

        //candidate_id

        $this->createIndex(
            'idx-candidate_work_log_feedback-candidate_id', 'candidate_work_log_feedback', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-candidate_work_log_feedback-candidate_id', 'candidate_work_log_feedback', 'candidate_id', 'candidate', 'candidate_id'
        );

        //store_id

        $this->createIndex(
            'idx-candidate_work_log_feedback-store_id', 'candidate_work_log_feedback', 'store_id'
        );

        $this->addForeignKey(
            'fk-candidate_work_log_feedback-store_id', 'candidate_work_log_feedback', 'store_id', 'store', 'store_id'
        );

        //company_id

        $this->createIndex(
            'idx-candidate_work_log_feedback-company_id', 'candidate_work_log_feedback', 'company_id'
        );

        $this->addForeignKey(
            'fk-candidate_work_log_feedback-company_id', 'candidate_work_log_feedback', 'company_id', 'company', 'company_id'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240618_144001_candidate_work_log cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240618_144001_candidate_work_log cannot be reverted.\n";

        return false;
    }
    */
}
