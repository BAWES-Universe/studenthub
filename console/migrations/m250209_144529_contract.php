<?php

use yii\db\Migration;
use common\models\Contract;
use common\models\Company;
use common\models\CandidateWorkHistory;
use yii\helpers\Console;

/**
 * Class m250209_144529_contract
 */
class m250209_144529_contract extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('contract')
            ->getColumn('candidate_id');

        if (!$columnData) {

            //candidate_id

            $this->addColumn('contract', 'candidate_id', $this->integer(11)->after('contract_uuid'));

            $this->createIndex('idx-contract-candidate_id', 'contract', 'candidate_id');

            $this->addForeignKey(
                'fk-contract-candidate_id',
                'contract',
                'candidate_id',
                'candidate',
                'candidate_id',
                'CASCADE'
            );

            //parent_company_id

            $this->addColumn('contract', 'parent_company_id', $this->integer(11)->after('candidate_id'));

            $this->createIndex('idx-contract-parent_company_id', 'contract', 'parent_company_id');

            $this->addForeignKey(
                'fk-contract-parent_company_id',
                'contract',
                'parent_company_id',
                'company',
                'company_id',
                'CASCADE'
            );

            //store_id

            $this->addColumn('contract', 'store_id', $this->integer(11)->after('parent_company_id'));

            $this->createIndex('idx-contract-store_id', 'contract', 'store_id');

            $this->addForeignKey(
                'fk-contract-store_id',
                'contract',
                'store_id',
                'store',
                'store_id',
                'CASCADE'
            );
        }

        //move work history to contract table

        $query = CandidateWorkHistory::find()
            ->joinWith('store')
            ->andWhere(['contract_uuid' => null]);
            
        $total = $query->count();
    
        Console::startProgress(0, $total);

        $n = 0;

        foreach ($query->batch(100) as $workHistories) {
            foreach ($workHistories as $history) {

                $store = $history->store;
                
                if (!$store) {
                    //get deleted old values
                    $store = Yii::$app->db->createCommand("SELECT * FROM store WHERE store_id = :store_id")
                        ->bindValue(':store_id', $history->store_id)
                        ->queryOne();
                }

                if (!$store) {
                    $n++;
                    continue;
                }

                $company =  Yii::$app->db->createCommand("SELECT * FROM company WHERE company_id = :company_id")
                    ->bindValue(':company_id', $store['company_id'])
                    ->queryOne();

                if (!$company) {
                    $n++;
                    continue;
                }
                
                $company_hourly_rate = $history->company_hourly_rate;

                if ($company_hourly_rate == 0) {
                    $company_hourly_rate = $company['company_hourly_rate'];
                }

                $model = new Contract();

                $model->scenario = Contract::SCENARIO_ASSIGN;// _TEMPLATE;

                $model->company_id = $store['company_id'];
                $model->parent_company_id = $company['parent_company_id'];
                $model->candidate_id = $history->candidate_id;
                $model->store_id = $store['store_id'];

                $model->type = Contract::TYPE_HOURLY;
                $model->start_date = $history->start_date;
                $model->end_date = $history->end_date;
                $model->transfer_cost = $history->transfer_cost;
                $model->currency_code = "KWD";
                $model->status = Contract::STATUS_ACTIVE;
                $model->amountDetails = [
                    'candidate_hourly_rate' => (double) $history->candidate_hourly_rate,
                    'company_hourly_rate' => (double) $company_hourly_rate
                ];

                if (!$model->save(false)) {
                    echo "error 1 #".$history->id.":". print_r( $model->errors, true);
                    die();
                }

                $history->contract_uuid = $model->contract_uuid;
                if (!$history->save(false)) {
                    echo "error 2 #".$history->id.":". print_r( $history->errors, true);
                    die();
                }

                $n++;
                
                Console::updateProgress($n, $total);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('contract', 'candidate_id');
        $this->dropColumn('contract', 'parent_company_id');
        $this->dropColumn('contract', 'store_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250209_144529_contract cannot be reverted.\n";

        return false;
    }
    */
}
