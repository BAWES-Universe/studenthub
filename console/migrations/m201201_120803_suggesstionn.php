<?php

use yii\db\Migration;

/**
 * Class m201201_120803_suggesstionn
 */
class m201201_120803_suggesstionn extends Migration
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

        $this->createTable('suggestion', [
            "suggestion_uuid" => $this->char(60),
            'request_uuid' => $this->char(60)->notNull(),
            'fulltimer_uuid' => $this->char(60),
            'candidate_id' => $this->integer(),
            'note_uuid' => $this->char(60)->notNull(),
            'suggestion_status' => $this->tinyInteger(2)->defaultValue(0)->comment('1-Suggested , 2- rejected, 3- accepted'),
            'suggestion_datetime' => $this->datetime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'suggestion', 'suggestion_uuid');

        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-suggestion-request_uuid',
            'suggestion',
            'request_uuid'
        );

        // add foreign key for table `request_uuid`
        $this->addForeignKey(
            'fk-suggestion-request_uuid',
            'suggestion',
            'request_uuid',
            'request',
            'request_uuid'
        );

        // creates index for column `fulltimer_uuid`
        $this->createIndex(
            'idx-suggestion-fulltimer_uuid',
            'suggestion',
            'fulltimer_uuid'
        );

        // add foreign key for table `fulltimer_uuid`
        $this->addForeignKey(
            'fk-suggestion-fulltimer_uuid',
            'suggestion',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid'
        );


        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-suggestion-candidate_id',
            'suggestion',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-suggestion-candidate_id',
            'suggestion',
            'candidate_id',
            'candidate',
            'candidate_id'
        );

        // creates index for column `note_uuid`
        $this->createIndex(
            'idx-suggestion-note_uuid',
            'suggestion',
            'note_uuid'
        );

        // add foreign key for table `note_uuid`
        $this->addForeignKey(
            'fk-suggestion-note_uuid',
            'suggestion',
            'note_uuid',
            'note',
            'note_uuid'
        );

        $this->alterColumn ('note', 'note_type', "Enum('Internal Note', 'Phone Call', 'Email', 'Meeting', 'Interview', 'Task', 'Suggested', 'Accepted', 'Rejected') DEFAULT 'Internal Note' AFTER `request_uuid`");
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
        echo "m201201_120803_suggesstionn cannot be reverted.\n";

        return false;
    }
    */
}
