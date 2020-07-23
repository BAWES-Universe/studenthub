<?php

use yii\db\Migration;

/**
 * Class m200721_082533_candidate_bank
 */
class m200721_082533_candidate_bank extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    { 
        $this->addColumn(
            'transfer_candidate', 
            'bank_id', 
            $this->integer(11)->null()->after('company_email')->comment("transfer to which bank")
        );
        
        $this->addColumn(
            'transfer_candidate', 
            'transfer_confirmation_id', 
            $this->string(128)->after('bank_id')->null()->unique()
        );

        $this->addColumn(
            'transfer_candidate', 
            'transfer_benef_name', 
            $this->char(60)->after('transfer_confirmation_id')->null()
        );
        
        $this->addColumn(
            'transfer_candidate', 
            'transfer_benef_iban', 
            $this->char(50)->after('transfer_benef_name')->null()
        );
        
        $this->createIndex('idx-transfer_candidate-bank_id', 'transfer_candidate', 'bank_id');
        $this->addForeignKey('fk-transfer_candidate-bank_id', 'transfer_candidate', 'bank_id', 'bank', 'bank_id');
    
        $tcs = $this->db->createCommand('select candidate_id from transfer_candidate group by candidate_id')->queryAll();
        
        foreach($tcs as $tc) {
            
            $candidate = $this->db->createCommand('select * from candidate where candidate_id="'. $tc['candidate_id'].'"')->queryOne();
            
            if(!$candidate) 
                continue;
            
            $sql = 'UPDATE transfer_candidate SET bank_id="'.$candidate['bank_id'].'", '
                    . 'transfer_benef_name="'.$candidate['bank_account_name'].'", '
                    . 'transfer_benef_iban="'.$candidate['candidate_iban'].'" WHERE candidate_id="'.$tc['candidate_id'].'"';
            
            $this->db->createCommand($sql)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200721_082533_candidate_bank cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200721_082533_candidate_bank cannot be reverted.\n";

        return false;
    }
    */
}
