<?php

use yii\db\Migration;

/**
 * Class m250205_112835_candidate_notified
 */
class m250205_112835_candidate_notified extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('transfer_candidate')
            ->getColumn('is_candidate_notified');

        if (!$columnData) {
            $this->addColumn("transfer_candidate", "is_candidate_notified",
                $this->boolean()->defaultValue(false)->after("paid"));
        }

        \admin\models\TransferCandidate::updateAll([
            "is_candidate_notified" => 1,
        ], [
            "paid" => 1
        ]);
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
        echo "m250205_112835_candidate_notified cannot be reverted.\n";

        return false;
    }
    */
}
