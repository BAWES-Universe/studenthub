<?php

use yii\db\Migration;

/**
 * Class m210323_094031_candidate_bank
 */
class m210323_094031_candidate_bank extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //find all transfer details without bank details

        $sql = 'select * from transfer_candidate left join candidate on candidate.candidate_id = transfer_candidate.candidate_id where transfer_candidate.bank_id IS NULL AND candidate.bank_id IS NOT NULL';

        $transfers = $this->db->createCommand ($sql)->queryAll ();

        $candidateIds = \yii\helpers\ArrayHelper::getColumn ($transfers, 'candidate_id');

        if(sizeof ($candidateIds) > 0) {

            $sql = "update candidate set bank_id = NULL, bank_account_name = NULL, candidate_iban = NULL WHERE candidate_id IN (" . implode (', ', $candidateIds) . ")";

            $this->db->createCommand ($sql)->execute ();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210323_094031_candidate_bank cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210323_094031_candidate_bank cannot be reverted.\n";

        return false;
    }
    */
}
