<?php

use yii\db\Migration;

/**
 * Class m241103_170837_session_notification
 */
class m241103_170837_session_notification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_notification", "candidate_working_hour_uuid", $this->char(60)->after("candidate_working_date_uuid"));

        // creates index for column `candidate_working_hour_uuid`
        $this->createIndex(
            'idx-candidate_notification-candidate_working_hour_uuid',
            'candidate_notification',
            'candidate_working_hour_uuid'
        );

        // add foreign key for table `candidate_working_hour`
        $this->addForeignKey(
            'fk-candidate_notification-candidate_working_hour_uuid',
            'candidate_notification',
            'candidate_working_hour_uuid',
            'candidate_working_hour',
            'candidate_working_hour_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241103_170837_session_notification cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241103_170837_session_notification cannot be reverted.\n";

        return false;
    }
    */
}
