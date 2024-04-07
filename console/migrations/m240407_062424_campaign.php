<?php

use yii\db\Migration;

/**
 * Class m240407_062424_campaign
 */
class m240407_062424_campaign extends Migration
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

        //todo: on each transfer marked as paid -> update total_revenue in all candidates' campaign
        $this->createTable('{{%campaign}}', [
            'utm_uuid'=> $this->char(60),
            'utm_source' => $this->string(100)->comment('e.g. newsletter, twitter, google, etc.'),
            'utm_medium' => $this->string(100)->comment('e.g. email, social, cpc, etc.'),
            'utm_campaign' => $this->string(100)->comment('e.g. promotion, sale, etc.'),
            'utm_content' => $this->string(100)->comment('Any call-to-action or headline, e.g. buy-now.'),
            'utm_term' => $this->string(100)->comment('Keywords for your paid search campaigns'),
            'no_of_signups' => $this->integer(11),
            'no_of_clicks' => $this->integer(11),
            'investment' => $this->decimal(10, 3),
            'total_revenue' => $this->decimal(10, 3)
                ->comment("Total revenue made by us"),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'campaign', 'utm_uuid');

        $this->addColumn('candidate', 'utm_uuid', $this->char(64)->after('candidate_id'));

        $this->createIndex('ind-candidate-utm_uuid', 'candidate', 'utm_uuid');

        $this->addForeignKey(
            'fk-candidate-utm_uuid', 'candidate', 'utm_uuid',
            'campaign', 'utm_uuid'
        );

        $this->addColumn('contact', 'utm_uuid', $this->char(64)->after('contact_uuid'));

        $this->createIndex('ind-contact-utm_uuid', 'contact', 'utm_uuid');

        $this->addForeignKey(
            'fk-contact-utm_uuid', 'contact', 'utm_uuid',
            'campaign', 'utm_uuid'
        );

        $this->addColumn('company_request', 'utm_uuid', $this->char(64)->after('company_request_uuid'));

        $this->createIndex('ind-company_request-utm_uuid', 'company_request', 'utm_uuid');

        $this->addForeignKey(
            'fk-company_request-utm_uuid', 'company_request', 'utm_uuid',
            'campaign', 'utm_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240407_062424_campaign cannot be reverted.\n";

        return false;
    }
    */
}
