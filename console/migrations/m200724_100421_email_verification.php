<?php

use yii\db\Migration;

/**
 * Class m200724_100421_email_verification
 */
class m200724_100421_email_verification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        
        $this->createTable('{{%candidate_email_verify_attempt}}', [
            'ceva_uuid' => $this->char(60),
            'candidate_email' => $this->string(50),
            'code' => $this->string(32),
            'ip_address' => $this->string(45),
            'created_at' => $this->dateTime()
        ], $tableOptions);
                
        $this->addPrimaryKey('PK', 'candidate_email_verify_attempt', 'ceva_uuid');
        
        $this->addColumn('candidate', 'candidate_new_email', $this->string(100)->null()->after('candidate_email'));
        $this->addColumn('candidate', 'candidate_email_verification', $this->boolean()->after('candidate_new_email'));
        $this->addColumn('candidate', 'candidate_limit_email', $this->dateTime()->after('candidate_email_verification'));
        
        $this->alterColumn('candidate', 'candidate_birth_date', $this->date()->null());
        
        $this->alterColumn('candidate', 'candidate_civil_id', $this->string()->null()->unique());
        $this->alterColumn('candidate', 'candidate_civil_expiry_date', $this->date()->null());
        $this->alterColumn('candidate', 'candidate_hourly_rate', $this->decimal(7,3)->null());
        $this->alterColumn('candidate', 'candidate_auth_key', $this->string(32)->null());
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
        echo "m200724_100421_email_verification cannot be reverted.\n";

        return false;
    }
    */
}
