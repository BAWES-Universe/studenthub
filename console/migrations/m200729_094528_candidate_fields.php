<?php

use yii\db\Migration;

/**
 * Class m200729_094528_candidate_fields
 */
class m200729_094528_candidate_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'candidate_driving_license', $this->boolean()->comment('1-yes, 2-no')->after('candidate_civil_photo_back'));
        $this->addColumn('candidate', 'candidate_resume', $this->string()->after('candidate_driving_license'));
        $this->addColumn('candidate', 'candidate_gender', $this->integer()->comment('1-male, 2-other, 3-gender')->after('candidate_name_ar'));
        $this->addColumn('candidate', 'candidate_objective', $this->string()->after('candidate_gender'));
        
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
            
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        
        //candidate_skill
        
        $this->createTable('{{%candidate_skill}}', [
            'candidate_skill_id' => $this->primaryKey(),
            'candidate_id' => $this->integer(11),
            'skill' => $this->string(128)->notNull(),
            'candidate_skill_created_at' => $this->dateTime()
        ], $tableOptions);
        
        $this->addForeignKey(
            'fk-candidate_skill-candidate_id',
            'candidate_skill',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        ); 

        //candidate_experience
        
        $this->createTable('{{%candidate_experience}}', [
            'candidate_experience_id' => $this->primaryKey(),
            'candidate_id' => $this->integer(11),
            'experience' => $this->string(128)->notNull(),
            'candidate_experience_created_at' => $this->dateTime()
        ], $tableOptions);
        
        $this->addForeignKey(
            'fk-candidate_experience-candidate_id',
            'candidate_experience',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        ); 
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200729_094528_candidate_fields cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200729_094528_candidate_fields cannot be reverted.\n";

        return false;
    }
    */
}
