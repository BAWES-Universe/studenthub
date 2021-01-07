<?php

use yii\db\Migration;

/**
 * Class m210107_091140_missing_transfer
 */
class m210107_091140_missing_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //check for sub company transfers without parent company transfers and

        $sql = "select * from transfer inner join company on company.company_id = transfer.company_id
            where company.parent_company_id IS NOT NULL AND transfer.parent_transfer_id is null
            AND transfer.deleted = 0";

        $transfersWithMissingParent = $this->db->createCommand($sql)->queryAll();

        foreach ($transfersWithMissingParent as $transferWithMissingParent) {

             //create parent transfer 

             $createTransferQuery = "INSERT INTO transfer SET company_id = '".$transferWithMissingParent['parent_company_id']."',total = '".$transferWithMissingParent['total']."',company_total= '".$transferWithMissingParent['company_total']."',payment_received_on='".$transferWithMissingParent['payment_received_on']."',transfer_status='".$transferWithMissingParent['transfer_status']."',start_date= '".$transferWithMissingParent['start_date']."',end_date= '".$transferWithMissingParent['end_date']."',transfer_created_at= '".$transferWithMissingParent['transfer_created_at']."',transfer_updated_at= '".$transferWithMissingParent['transfer_updated_at']."'";

            if($transferWithMissingParent['transfer_updated_by']) {
                $createTransferQuery .= ",transfer_updated_by= '".$transferWithMissingParent['transfer_updated_by']."'";
            }
            
            if($transferWithMissingParent['transfer_created_by']) {
                $createTransferQuery .= ",transfer_created_by= '".$transferWithMissingParent['transfer_created_by']."'";
            }

            $this->db->createCommand($createTransferQuery)->execute();

            //get added transfer id 

            $transfer_id = Yii::$app->db->getLastInsertID();

            //copy transfer candidates 

            $candidteQuery = "select * from transfer_candidate where transfer_id = ". $transferWithMissingParent['transfer_id'] . " AND transfer_candidate.deleted = 0";
          
            $candidates = $this->db->createCommand($candidteQuery)->queryAll();

            foreach($candidates as $transfer_candidate) {

                $parentCompany = $this->db->createCommand('select * from company where company_id="'.$transferWithMissingParent['parent_company_id'].'"')->queryOne();

                $createTransferCandidateQuery = "INSERT INTO transfer_candidate SET 
                        transfer_id=".$transfer_id.",
                        candidate_id=".$transfer_candidate['candidate_id'].",
                        store_id=".$transfer_candidate['store_id'].",
                        store_name='".$transfer_candidate['store_name']."',
                        company_id='".$parentCompany['company_id']."',
                        company_name='".$parentCompany['company_name']."',
                        company_email='".$parentCompany['company_email']."',
                        bank_id='".$transfer_candidate['bank_id']."',
                        transfer_confirmation_id='".$transfer_candidate['transfer_confirmation_id']."',
                        transfer_benef_name='".$transfer_candidate['transfer_benef_name']."',
                        transfer_benef_iban='".$transfer_candidate['transfer_benef_iban']."',
                        candidate_hourly_rate='".$transfer_candidate['candidate_hourly_rate']."',
                        company_hourly_rate='".$transfer_candidate['company_hourly_rate']."',
                        hours='".$transfer_candidate['hours']."',
                        bonus='".$transfer_candidate['bonus']."',
                        bonus_commission='".$transfer_candidate['bonus_commission']."',
                        transfer_cost='".$transfer_candidate['transfer_cost']."',
                        deleted='".$transfer_candidate['deleted']."',
                        paid='".$transfer_candidate['paid']."',
                        tc_created_at='".$transfer_candidate['tc_created_at']."',
                        tc_updated_at='".$transfer_candidate['tc_updated_at']."'";

                if($transfer_candidate['transfer_file_id']) {
                    $createTransferCandidateQuery .= ",transfer_file_id=".$transfer_candidate['transfer_file_id'];
                }

                $this->db->createCommand($createTransferCandidateQuery)->execute();
            }

            //assign new id to transfer 

            $sqlAssignTransfer = "update transfer set parent_transfer_id =". $transfer_id ." where transfer_id = ".$transferWithMissingParent['transfer_id'];

            $this->db->createCommand($sqlAssignTransfer)->execute();
        }
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
        echo "m210107_091140_missing_transfer cannot be reverted.\n";

        return false;
    }
    */
}
