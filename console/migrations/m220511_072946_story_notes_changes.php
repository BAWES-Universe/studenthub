<?php

use yii\db\Migration;

/**
 * Class m220511_072946_story_notes_changes
 */
class m220511_072946_story_notes_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('note')
            ->getColumn('story_uuid');

        if (!$columnData) 
        {
            $this->addColumn('note', 'story_uuid', $this->char(60)->null()->after('suggestion_uuid'));

            $this->createIndex(
                'idx-note-story_uuid',
                'note',
                'story_uuid'
            );

            $this->addForeignKey(
                'fk-note-story_uuid',
                'note',
                'story_uuid',
                'story',
                'story_uuid'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220511_072946_story_notes_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220511_072946_story_notes_changes cannot be reverted.\n";

        return false;
    }
    */
}
