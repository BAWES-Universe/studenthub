<?php

use yii\db\Migration;

/**
 * Class m250320_162139_cron_log
 */
class m250320_162139_cron_log extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('cron_log', [
            'id' => $this->primaryKey(),
            'task' => $this->string()->notNull(),
            'last_ran_at' => $this->dateTime(),
            "last_output" => $this->text(),
        ]);

        Yii::$app->db->createCommand()->batchInsert(
            "cron_log",
            ['id', 'task', 'last_ran_at', 'last_output'],
            [
                [1, 'every-minute', null, null],
                [2, 'process-transfer-files', null, null],
                [3, 'process-campaign', null, null],
                [4, 'algolia', null, null],
                [5, 'payable-candidate-notification', null, null],
                [6, 'check-daily-attendance', null, null],
                [7, 'summary', null, null],
                [8, 'daily', null, null],
                [9, 'weekly', null, null],
                [10, 'end-of-month', null, null],
                [11, 'report/recruiter', null, null],
                [12, 'fill-civil-id-expiry-date', null, null],
                [13, 'fill-civil-id-expiry-date-not-assigned', null, null],
                [14, 'validate-civil-id', null, null],
                [15, 'check-if-candidate-total-mismatch', null, null],
                [16, 'gen-hit-map', null, null],
                [17, 'mid-month', null, null],
                [18, 'update-candidate-stats', null, null],
                [19, 'update-company-stats', null, null],
            ])->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('cron_log');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250320_162139_cron_log cannot be reverted.\n";

        return false;
    }
    */
}
