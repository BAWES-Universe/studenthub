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
        echo "m250130_115508_2step_auth cannot be reverted.\n";

        return false;
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
