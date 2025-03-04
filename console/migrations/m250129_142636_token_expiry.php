<?php

use yii\db\Migration;

/**
 * Class m250129_142636_token_expiry
 */
class m250129_142636_token_expiry extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('admin_token')
            ->getColumn('ip_address');

        if (!$columnData) {
            return true;
        }

        $this->addColumn("admin_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        $this->addColumn("candidate_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        $this->addColumn("company_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        $this->addColumn("contact_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        $this->addColumn("inspector_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        $this->addColumn("manager_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        $this->addColumn("staff_token", "ip_address",
            $this->string(45)->after("token_expiry_datetime"));

        \common\models\AdminToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);

        \common\models\CandidateToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);

        /*\common\models\CompanyToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);*/

        \common\models\ContactToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);

        \common\models\InspectorToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);

        \common\models\ManagerToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);

        \common\models\StaffToken::updateAll([
            "token_expiry_datetime" => date('Y-m-d H:i:s', strtotime("+1 month"))
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn("admin_token", "ip_address");

        $this->dropColumn("candidate_token", "ip_address");

        $this->dropColumn("company_token", "ip_address");

        $this->dropColumn("contact_token", "ip_address");

        $this->dropColumn("inspector_token", "ip_address");

        $this->dropColumn("manager_token", "ip_address");

        $this->dropColumn("staff_token", "ip_address");

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250129_142636_token_expiry cannot be reverted.\n";

        return false;
    }
    */
}
