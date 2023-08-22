<?php

use yii\db\Migration;

/**
 * Class m230822_091618_sql_optimize
 */
class m230822_091618_sql_optimize extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('company', 'company_next_followup_datetime',
            $this->dateTime()->defaultExpression('current_timestamp()') ->after('company_last_followup_datetime'));

        /*$query = \admin\models\Company::find();

        $week = 60 * 60 * 24 * 7;

        foreach ($query->batch(200) as $companies) {
            foreach ($companies as $company) {
                 company_followup_interval_weeks


                $company->company_next_followup_datetime = date('Y-m-d') strtotime($company->company_last_followup_datetime) + $week
                    //UPDATE company SET company_next_followup_datetime = DATE_ADD(company_last_followup_datetime,INTERVAL company_followup_interval_weeks WEEK)

            }
        }*/

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230822_091618_sql_optimize cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230822_091618_sql_optimize cannot be reverted.\n";

        return false;
    }
    */
}
