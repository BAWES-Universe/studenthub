<?php

use yii\db\Migration;

/**
 * Class m241029_193106_contract
 */
class m241029_193106_contract extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contract}}', [
            "contract_uuid" => $this->char(60),
            "company_id" => $this->integer(11)->notNull(),
            "type" => $this->string()->notNull(),
            "detail" => $this->text(),
            "start_date" => $this->dateTime(),
            "end_date" => $this->dateTime(),
            "transfer_cost" => $this->decimal(12, 3)->defaultValue(0),
            "currency_code" => $this->char(3)->defaultValue("KWD"),
            "status" =>  $this->tinyInteger(2)->notNull()->defaultValue(0),
            "created_by" => $this->integer(11),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-contract-contract_uuid', 'contract', "contract_uuid");

        /*// creates index for column `currency_id`
        $this->createIndex(
            'idx-contract-currency_id',
            'contract',
            'currency_id'
        );

        // add foreign key for table `currency`
        $this->addForeignKey(
            'fk-contract-currency_id',
            'contract',
            'currency_id',
            'currency',
            'currency_id'
        );*/

        // creates index for column `created_by`
        $this->createIndex(
            'idx-contract-created_by',
            'contract',
            'created_by'
        );

        // add foreign key for table `staff`
        $this->addForeignKey(
            'fk-contract-created_by',
            'contract',
            'created_by',
            'staff',
            'staff_id'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-contract-company_id',
            'contract',
            'company_id'
        );

        // add foreign key for table `company`
        $this->addForeignKey(
            'fk-contract-company_id',
            'contract',
            'company_id',
            'company',
            'company_id'
        );

         //salary

        $this->createTable('{{%monthly_salary_contract}}', [
            "ms_contract_uuid" => $this->char(60),
            "contract_uuid" => $this->char(60)->notNull(),
            "candidate_total" => $this->decimal(12, 3)->notNull(),
            "company_total" => $this->decimal(12, 3)->notNull(),
            "salary_day" => $this->tinyInteger(2)->null()->comment("e.g., 5th of the month")
        ], $tableOptions);

        $this->addPrimaryKey('pk-monthly_salary_contract', 'monthly_salary_contract', "ms_contract_uuid");

        // creates index for column `contract_uuid`
        $this->createIndex(
            'idx-monthly_salary_contract-contract_uuid',
            'monthly_salary_contract',
            'contract_uuid'
        );

        // add foreign key for table `contract`
        $this->addForeignKey(
            'fk-monthly_salary_contract-contract_uuid',
            'monthly_salary_contract',
            'contract_uuid',
            'contract',
            'contract_uuid'
        );

        //hourly_contract

        $this->createTable('{{%hourly_contract}}', [
            "h_contract_uuid" => $this->char(60),
            "contract_uuid" => $this->char(60)->notNull(),
            "candidate_hourly_rate" => $this->decimal(12, 3)->notNull(),
            "company_hourly_rate" => $this->decimal(12, 3)->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-hourly_contract', 'hourly_contract', "h_contract_uuid");

        // creates index for column `contract_uuid`
        $this->createIndex(
            'idx-hourly_contract-contract_uuid',
            'hourly_contract',
            'contract_uuid'
        );

        // add foreign key for table `contract`
        $this->addForeignKey(
            'fk-hourly_contract-contract_uuid',
            'hourly_contract',
            'contract_uuid',
            'contract',
            'contract_uuid'
        );

        //fixed_price_contract

        $this->createTable('{{%fixed_price_contract}}', [
            "fp_contract_uuid" => $this->char(60),
            "contract_uuid" => $this->char(60)->notNull(),
            "candidate_total" => $this->decimal(12, 3)->notNull(),
            "company_total" => $this->decimal(12, 3)->notNull(),
            "completion_percentage" => $this->tinyInteger(3),
        ], $tableOptions);

        $this->addPrimaryKey('pk-fp_contract_uuid', 'fixed_price_contract', "fp_contract_uuid");

        // creates index for column `contract_uuid`
        $this->createIndex(
            'idx-fixed_price_contract-contract_uuid',
            'fixed_price_contract',
            'contract_uuid'
        );

        // add foreign key for table `contract`
        $this->addForeignKey(
            'fk-fixed_price_contract-contract_uuid',
            'fixed_price_contract',
            'contract_uuid',
            'contract',
            'contract_uuid'
        );

        //candidate_work_history

        $this->addColumn("candidate_work_history", "contract_uuid", $this->char(60)->after("candidate_id"));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%contract}}');
        $this->dropTable('{{%fixed_price_contract}}');
        $this->dropTable('{{%hourly_contract}}');
        $this->dropTable('{{%monthly_salary_contract}}');

        $this->dropColumn("candidate_work_history", "contract_uuid");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241029_193106_contract cannot be reverted.\n";

        return false;
    }
    */
}
