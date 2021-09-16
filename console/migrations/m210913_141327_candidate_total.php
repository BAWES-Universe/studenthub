<?php

use admin\models\TransferCandidate;
use yii\db\Migration;

/**
 * Class m210913_141327_candidate_total
 */
class m210913_141327_candidate_total extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $transferCandidates = TransferCandidate::find()
            ->payable()
            ->havingBankInfo()
            ->all();

        foreach ($transferCandidates as $transferCandidate) {
            if($transferCandidate->totalPaidToCandidate != $transferCandidate->candidate_total) {
                $transferCandidate->candidate_total = $transferCandidate->totalPaidToCandidate;
                $transferCandidate->save(false);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210913_141327_candidate_total cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210913_141327_candidate_total cannot be reverted.\n";

        return false;
    }
    */
}
