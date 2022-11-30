<?php

use yii\db\Migration;

/**
 * Class m221130_112258_candidate_working_hour_trigger
 */
class m221130_112258_candidate_working_hour_trigger extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            DROP TRIGGER IF EXISTS `after_candidate_working_hour_update`;
            CREATE TRIGGER `after_candidate_working_hour_update` BEFORE UPDATE ON `candidate_working_hour`
            FOR EACH ROW
            IF (NEW.total_time < 0 OR NEW.total_time IS NULL) THEN
            SET NEW.total_time=TIMESTAMPDIFF(SECOND,OLD.start_time,NEW.end_time);
            END IF"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP TRIGGER IF EXISTS `after_candidate_working_hour_update`");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221130_112258_candidate_working_hour_trigger cannot be reverted.\n";

        return false;
    }
    */
}
