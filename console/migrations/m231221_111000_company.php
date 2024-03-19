<?php

use yii\db\Migration;

/**
 * Class m231221_111000_company
 */
class m231221_111000_company extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('company', 'last_payment_datetime', $this->dateTime()->after('company_updated_at'));
        $this->addColumn('company', 'last_request_datetime', $this->dateTime()->after('company_updated_at'));

        $query = \common\models\Company::find()
            ->andWhere(['company.deleted' => 0]);

        foreach ($query->batch(100) as $companies) {

            foreach ($companies as $company) {

                $latestTransfer = $company->getTransfers()
                    ->andWhere(['IN', 'transfer_status', [3,4]])
                    ->orderBy('transfer_created_at DESC')//payment_received_on DESC,
                    ->one();

                if($latestTransfer) {
                    $company->last_payment_datetime = !empty($latestTransfer->payment_received_on) ?
                        $latestTransfer->payment_received_on: $latestTransfer->transfer_updated_at;
                }

                $latestRequest = $company->getRequests()
                    ->orderBy('request_created_datetime DESC')
                    ->one();

                if($latestRequest) {
                    $company->last_request_datetime = $latestRequest->request_created_datetime;
                }

                $company->save(false);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231221_111000_company cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231221_111000_company cannot be reverted.\n";

        return false;
    }
    */
}
