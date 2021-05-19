<?php

use common\models\Transfer;
use common\models\TransferCandidate;
use yii\db\Migration;


/**
 * Class m210519_140346_fix_transfer
 */
class m210519_140346_fix_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //recalculate transfer total

        $transferCandidates = TransferCandidate::find()
            ->joinWith ('candidate', true, 'left join')
            ->filterWhere ([
                'paid' => 0
            ])
            ->andWhere (new \yii\db\Expression('candidate.candidate_hourly_rate != transfer_candidate.candidate_hourly_rate'))
            ->select('transfer_id')
            ->all();

        $transferCandidatesIds = \yii\helpers\ArrayHelper::getColumn ($transferCandidates, 'transfer_id');

        $transfers = Transfer::find()
            ->filterWhere (['in', 'transfer_id', $transferCandidatesIds])
            ->all();

        foreach($transfers as $transfer) {

            $total = 0;

            foreach ($transfer->transferCandidates as $transferCandidate)
            {
                if ((int)$transferCandidate['hours'] > 0 || $transferCandidate['bonus'] > 0)
                {
                    //total amount we will pay to bank
                    $total += $transferCandidate['bonus'] - $transferCandidate['bonus_commission'] + ($transferCandidate['hours'] * $transferCandidate->candidate->candidate_hourly_rate) + $transferCandidate['transfer_cost'];
                }

                $transferCandidate->candidate_hourly_rate = $transferCandidate->candidate->candidate_hourly_rate;
                $transferCandidate->save();
            }

            $transfer->total = $total;
            $transfer->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210519_140346_fix_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210519_140346_fix_transfer cannot be reverted.\n";

        return false;
    }
    */
}
