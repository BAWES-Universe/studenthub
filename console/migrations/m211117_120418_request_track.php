<?php

use yii\db\Migration;

/**
 * Class m211117_120418_request_track
 */
class m211117_120418_request_track extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableSchema = $this->getDb()->getSchema()->getTableSchema('request');
        
        if ($tableSchema && !isset($tableSchema->columns['request_started_at'])) {
            $this->addColumn ('request', 'request_started_at',
                $this->dateTime ()->after('num_hours_followup_interval'));
        }

        if ($tableSchema && !isset($tableSchema->columns['request_assigned_at'])) {
            $this->addColumn ('request', 'request_assigned_at',
                $this->dateTime ()->after('request_started_at'));
        }

        if ($tableSchema && !isset($tableSchema->columns['request_delivered_at'])) {
            $this->addColumn ('request', 'request_delivered_at',
                $this->dateTime ()->after('request_assigned_at'));
        }

        if ($tableSchema && !isset($tableSchema->columns['request_cancelled_at'])) {
            $this->addColumn ('request', 'request_cancelled_at',
                $this->dateTime ()->after('request_delivered_at'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211117_120418_request_track cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211117_120418_request_track cannot be reverted.\n";

        return false;
    }
    */
}
