<?php

use yii\db\Migration;

/**
 * Class m201208_080317_work_history_table
 */
class m201208_080317_work_history_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate_work_history','company_id',$this->integer(11)->after('store_id'));
        $this->addColumn('candidate_work_history','parent_company_id',$this->integer(11)->after('company_id'));

        $this->createIndex(
            'idx-candidate_work_history-company_id',
            'candidate_work_history',
            'company_id'
        );

        $this->addForeignKey(
            'fk-candidate_work_history-company_id',
            'candidate_work_history',
            'company_id',
            'company',
            'company_id'

        );

        $this->createIndex(
            'idx-candidate_work_history-parent_company_id',
            'candidate_work_history',
            'parent_company_id'
        );

        $this->addForeignKey(
            'fk-candidate_work_history-parent_company_id',
            'candidate_work_history',
            'parent_company_id',
            'company',
            'company_id'

        );

        $q = 'SELECT `store`.store_id,`company`.company_id,`company`.parent_company_id FROM `store` ';
        $q .= 'left join `company` on company.company_id = `store`.`company_id`';
        $rows = Yii::$app->db->createCommand($q)->queryAll();

        if ($rows && count($rows) > 0) {
            foreach ($rows as $store) {
                $company_id = $store['company_id'];
                $store_id = $store['store_id'];
                $parent_company_id = ($store['parent_company_id']) ? $store['parent_company_id'] : $store['company_id'];
                $update = "update `candidate_work_history` set company_id = {$company_id}, ";
                $update .= "`parent_company_id`={$parent_company_id} where `store_id`=".$store_id;
                Yii::$app->db->createCommand($update)->execute();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        $this->dropForeignKey(
            'fk-company-company_id',
            'candidate_work_history'
        );

        $this->dropForeignKey(
            'fk-candidate_work_history-parent_company_id',
            'candidate_work_history'
        );

        $this->dropColumn('candidate_work_history','company_id');
        $this->dropColumn('candidate_work_history','parent_company_id');

        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201208_080317_work_history_table cannot be reverted.\n";

        return false;
    }
    */
}
