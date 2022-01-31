<?php

use yii\db\Migration;

/**
 * Class m220112_103815_suggestion_note_story_uuid
 */
class m220112_103815_suggestion_note_story_uuid extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('suggestion')
            ->getColumn('story_uuid');

        if (!$columnData) {
            $this->addColumn('suggestion', 'story_uuid', $this->char(60)->null()->after('note_uuid'));

            $this->createIndex(
                'idx-suggestion-story_uuid',
                'suggestion',
                'story_uuid'
            );

            $this->addForeignKey(
                'fk-suggestion-story_uuid',
                'suggestion',
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
        echo "m220112_103815_suggestion_note_story_uuid cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220112_103815_suggestion_note_story_uuid cannot be reverted.\n";

        return false;
    }
    */
}
