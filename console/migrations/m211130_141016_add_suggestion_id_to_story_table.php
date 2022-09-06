<?php

use yii\db\Migration;

/**
 * Class m211130_141016_add_suggestion_id_to_story_table
 */
class m211130_141016_add_suggestion_id_to_story_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('story', 'suggestion_uuid', $this->char(60)->null()->after('request_uuid'));

        // creates index for column `suggestion_uuid`
        $this->createIndex(
            'idx-story-suggestion_uuid',
            'story',
            'suggestion_uuid'
        );

        // add foreign key for table `suggestion_uuid`
        $this->addForeignKey(
            'fk-story-suggestion_uuid',
            'story',
            'suggestion_uuid',
            'suggestion',
            'suggestion_uuid'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey (
            'fk-story-suggestion_uuid',
            'story'
        );

        $this->dropIndex('idx-story-suggestion_uuid', 'story');

        return false;
    }

}