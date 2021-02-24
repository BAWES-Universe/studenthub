<?php

use yii\db\Migration;

/**
 * Class m210223_135821_invitation_note
 */
class m210223_135821_invitation_note extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*$this->addColumn ('note', 'invitation_uuid', $this->char (60)->after('request_uuid'));

        // creates index for column `invitation_uuid`
        $this->createIndex(
            'idx-note-invitation_uuid',
            'note',
            'invitation_uuid'
        );

        // add foreign key for table `candidate`
        $this->addForeignKey(
            'fk-note-invitation_uuid',
            'note',
            'invitation_uuid',
            'invitation',
            'invitation_uuid'
        );*/

        $this->alterColumn ('note', 'note_type', $this->string(100)->defaultValue ('Internal Note'));
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
        echo "m210223_135821_invitation_note cannot be reverted.\n";

        return false;
    }
    */
}
