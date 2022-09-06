<?php

use common\models\Story;
use yii\db\Migration;

/**
 * Class m211224_102328_invitation
 */
class m211224_102328_invitation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('invitation', 'story_uuid', $this->char(60)->after('request_uuid'));

        $this->createIndex (
            'idx-invitation-story_uuid',
            'invitation',
            'story_uuid'
        );

        $this->addForeignKey (
            'fk-invitation-story_uuid',
            'invitation',
            'story_uuid',
            'story',
            'story_uuid',
            'RESTRICT',
            'RESTRICT'
        );

        $invitations = \common\models\Invitation::find ()->all();

        foreach($invitations as $invitation) {

            $story = Story::findOne([
                'request_uuid' => $invitation->request_uuid,
                'staff_id' => $invitation->invitation_created_by_staff
            ]);

            if(!$story) {
                continue;
            }

            $invitation->story_uuid = $story->story_uuid;
            $invitation->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211224_102328_invitation cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211224_102328_invitation cannot be reverted.\n";

        return false;
    }
    */
}