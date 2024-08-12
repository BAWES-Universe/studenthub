<?php

use yii\db\Migration;

/**
 * Class m240812_080436_transfer_rate
 */
class m240812_080436_transfer_rate extends Migration
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

        //transfer_cost

        $this->createTable('{{%transfer_cost}}', [
            "candidate_id" => $this->integer(11)->notNull(),
            "company_id" => $this->integer(11)->notNull(),
            "transfer_cost" => $this->decimal(12,3)->notNull(),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-transfer_cost', 'transfer_cost', ['candidate_id', 'company_id']);

        //candidate_id

        $this->createIndex(
            'idx-transfer_cost-candidate_id', 'transfer_cost', 'candidate_id'
        );

        $this->addForeignKey(
            'fk-transfer_cost-candidate_id', 'transfer_cost', 'candidate_id',
            'candidate', 'candidate_id', "CASCADE"
        );

        //company_id

        $this->createIndex(
            'idx-transfer_cost-company_id', 'transfer_cost', 'company_id'
        );

        $this->addForeignKey(
            'fk-transfer_cost-company_id', 'transfer_cost', 'company_id',
            'company', 'company_id', "CASCADE"
        );

        //candidate_work_history

        $this->addColumn("candidate_work_history", "transfer_cost", $this->decimal(12,3)->null());

        //transfer

        $this->addColumn("transfer", "transfer_cost", $this->decimal(12,3)->defaultValue(0));

    //on store assignment "override transfer cost" + show company transfer cost for that candidate + default transfer cost

    //on transfer creation + update in staff + company app > get transfer cost from store, company, default transfer cost

    //candidate listing in company app + staff app + admin app > show effected transfer cost

    //include transfer cost in receipt + invoices pdf + transfer detail pages in staff + admin + company app

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn("candidate_work_history", "transfer_cost");
        $this->dropTable('{{%transfer_cost}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240812_080436_transfer_rate cannot be reverted.\n";

        return false;
    }
    */
}
