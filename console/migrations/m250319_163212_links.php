<?php

use yii\db\Migration;

/**
 * Class m250319_163212_links
 */
class m250319_163212_links extends Migration
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

        //candidate_link

        $this->createTable('{{%candidate_link}}', [
            "cl_uuid" => $this->char(60),
            "candidate_id" => $this->integer(11)->notNull(),
            "title" => $this->string()->notNull(),
            "url" => $this->string()->notNull(),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-candidate_link-cl_uuid',
            'candidate_link', "cl_uuid");

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-candidate_link-candidate_id',
            'candidate_link',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-candidate_link-candidate_id',
            'candidate_link',
            'candidate_id',
            'candidate',
            'candidate_id',
            "CASCADE",
            "CASCADE"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250319_163212_links cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250319_163212_links cannot be reverted.\n";

        return false;
    }
    */
}
