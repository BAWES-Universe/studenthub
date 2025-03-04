<?php

use yii\db\Migration;

/**
 * Class m250130_115508_2step_auth
 */
class m250130_115508_2step_auth extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('admin')
            ->getColumn('enable_two_step_auth');

        if (!$columnData) {
            return true;
        }

        //enable_two_step_auth

        $this->addColumn("admin", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("admin_password_reset_token"));

        $this->addColumn("candidate", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("candidate_password_reset_token"));

        /*
        $this->addColumn("company", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("contact_password_reset_token"));
*/

        $this->addColumn("contact", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("contact_password_reset_token"));

        $this->addColumn("inspector", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("inspector_password_reset_token"));

        $this->addColumn("store_manager", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("password_reset_token"));

        $this->addColumn("staff", "enable_two_step_auth",
            $this->boolean()->defaultValue(false)->after("staff_password_reset_token"));

        //otp

        $this->addColumn("admin_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        $this->addColumn("candidate_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        $this->addColumn("company_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        $this->addColumn("contact_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        $this->addColumn("inspector_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        $this->addColumn("manager_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        $this->addColumn("staff_token", "otp",
            $this->char(5)->after("token_expiry_datetime"));

        //total_attempt

        $this->addColumn("admin_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));

        $this->addColumn("candidate_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));

        $this->addColumn("company_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));

        $this->addColumn("contact_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));

        $this->addColumn("inspector_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));

        $this->addColumn("manager_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));

        $this->addColumn("staff_token", "total_attempt",
            $this->tinyInteger(1)->after("token_expiry_datetime")->defaultValue(0));


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn("admin", "enable_two_step_auth");

        $this->dropColumn("candidate", "enable_two_step_auth");

        /*
        $this->dropColumn("company", "enable_two_step_auth");
*/

        $this->dropColumn("contact", "enable_two_step_auth");

        $this->dropColumn("inspector", "enable_two_step_auth");

        $this->dropColumn("store_manager", "enable_two_step_auth");

        $this->dropColumn("staff", "enable_two_step_auth");

        //otp

        $this->dropColumn("admin_token", "otp");

        $this->dropColumn("candidate_token", "otp");

        $this->dropColumn("company_token", "otp");

        $this->dropColumn("contact_token", "otp");

        $this->dropColumn("inspector_token", "otp");

        $this->dropColumn("manager_token", "otp");

        $this->dropColumn("staff_token", "otp");

        //total_attempt

        $this->dropColumn("admin_token", "total_attempt");

        $this->dropColumn("candidate_token", "total_attempt");

        $this->dropColumn("company_token", "total_attempt");

        $this->dropColumn("contact_token", "total_attempt");

        $this->dropColumn("inspector_token", "total_attempt");

        $this->dropColumn("manager_token", "total_attempt");

        $this->dropColumn("staff_token", "total_attempt");

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250130_115508_2step_auth cannot be reverted.\n";

        return false;
    }
    */
}
