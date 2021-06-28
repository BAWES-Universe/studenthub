<?php

use yii\db\Migration;

/**
 * Class m210628_063748_transfer_candidate_total_fields
 */
class m210628_063748_transfer_candidate_total_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('transfer_candidate','candidate_total',$this->decimal(10,3)->null()->after('transfer_cost'));
        $this->addColumn('transfer_candidate','company_total',$this->decimal(10,3)->null()->after('candidate_total'));

        Yii::$app->db->createCommand("update transfer_candidate set `bonus_commission`='0.0' where bonus_commission is null")->execute();

        $candidateTotalQuery = 'update transfer_candidate set `candidate_total`=(((`candidate_hourly_rate`*`hours`) + (`bonus`-`bonus_commission`)) +`transfer_cost`) where 1';
        Yii::$app->db->createCommand($candidateTotalQuery)->execute();

        $companyTotalQuery = 'update transfer_candidate set `company_total`=((`company_hourly_rate`*`hours`) + `bonus`) where 1';
        Yii::$app->db->createCommand($companyTotalQuery)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('transfer_candidate','candidate_total');
        $this->dropColumn('transfer_candidate','company_total');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210628_063748_transfer_candidate_total_fields cannot be reverted.\n";

        return false;
    }
    */
}
