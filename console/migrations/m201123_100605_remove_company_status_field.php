<?php

use yii\db\Migration;

/**
 * Class m201123_100605_remove_company_status_field
 */
class m201123_100605_remove_company_status_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('company','company_status');
        $this->addColumn('company','total_candidate',$this->bigInteger()->after('company_followup')->defaultValue(0));
        $this->addColumn('company','no_of_active_requests',$this->integer()->after('total_candidate')->defaultValue(0));
        $this->addColumn('company','is_request_updates_in_30_days',$this->tinyInteger(1)->after('no_of_active_requests')->defaultValue(0));

        // update total candidate
        $query = Yii::$app->db->createCommand('select sum(store_total_candidates) as total, company.company_id,company.parent_company_id from store left join company on company.company_id = store.company_id group by company.company_id HAVING total > 0')->queryAll();
        foreach($query as $each) {
            $id = ($each['parent_company_id']) ? $each['parent_company_id'] : $each['company_id'];
            $total = $each['total'];
            Yii::$app->db->createCommand()->update('company', ['total_candidate' => new \yii\db\Expression('`total_candidate` + '.$total)], 'company_id = '.$id)->execute();
        }

        // update request
        $requestQuery = Yii::$app->db->createCommand("select count(*) as total, company.company_id,company.parent_company_id from request left join company on company.company_id = request.company_id where request_status = 'started' group by company_id")->queryAll();
        foreach($requestQuery as $eachRequest) {
            $idRequest = ($eachRequest['parent_company_id']) ? $eachRequest['parent_company_id'] : $eachRequest['company_id'];
            Yii::$app->db->createCommand()->update('company', ['no_of_active_requests' => new \yii\db\Expression('`no_of_active_requests` + '.$eachRequest['total'])], 'company_id = '.$idRequest)->execute();
        }

        // update request in last 30 days
        $requestQueryLast30Days = Yii::$app->db->createCommand("select count(*) as total, company.company_id,company.parent_company_id from request left join company on company.company_id = request.company_id where request.`request_updated_datetime` >= DATE_SUB(NOW(),INTERVAL 30 DAY) group by company_id")->queryAll();
        foreach($requestQueryLast30Days as $eachRequestIn30Days) {
            $id30days = ($eachRequestIn30Days['parent_company_id']) ? $eachRequestIn30Days['parent_company_id'] : $eachRequestIn30Days['company_id'];
            Yii::$app->db->createCommand()->update('company', ['is_request_updates_in_30_days' => ($eachRequestIn30Days['total']) ? 1:0], 'company_id = '.$id30days)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('company','company_status',$this->smallInteger(6)->after('company_last_followup_datetime'));
        $this->dropColumn('company','total_candidate');
        $this->dropColumn('company','no_of_active_requests');
        $this->dropColumn('company','is_request_updates_in_30_days');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201123_100605_remove_company_status_field cannot be reverted.\n";

        return false;
    }
    */
}
