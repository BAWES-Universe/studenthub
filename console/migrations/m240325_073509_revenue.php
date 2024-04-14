<?php

use yii\db\Migration;

/**
 * Class m240325_073509_revenue
 */
class m240325_073509_revenue extends Migration
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

        $this->createTable('{{%candidate_stats}}', [
            'cs_uuid' => $this->char(60),
            "candidate_id" => $this->integer(11),
            "total_revenue" => $this->decimal(12, 3)->defaultValue(0),
            "currency_code" => $this->char(3)->defaultValue("KWD"),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_stats', 'cs_uuid');

        // creates index for column `currency_code`
        $this->createIndex(
            'idx-candidate_stats-currency_code',
            'candidate_stats',
            'currency_code'
        );

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-candidate_stats-candidate_id',
            'candidate_stats',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-candidate_stats-candidate_id',
            'candidate_stats',
            'candidate_id',
            'candidate',
            'candidate_id',
            'SET NULL'
        );

        $this->createTable('{{%company_stats}}', [
            'cs_uuid' => $this->char(60),
            'company_id' => $this->integer(11),
            "total_revenue" => $this->decimal(12, 3)->defaultValue(0),
            "currency_code" => $this->char(3)->defaultValue("KWD"),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'company_stats', 'cs_uuid');

        // creates index for column `currency_code`
        $this->createIndex(
            'idx-company_stats-currency_code',
            'company_stats',
            'currency_code'
        );

        // creates index for column `company_id`
        $this->createIndex(
            'idx-company_stats-company_id',
            'company_stats',
            'company_id'
        );

        // add foreign key for table `company_id`
        $this->addForeignKey(
            'fk-company_stats-company_id',
            'company_stats',
            'company_id',
            'company',
            'company_id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("{{%candidate_stats}}");
        $this->dropTable("{{%company_stats}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240325_073509_revenue cannot be reverted.\n";

        return false;
    }
    */
}
