<?php

use yii\db\Migration;

/**
 * Class m240728_162148_invitation_seen_in
 */
class m240728_162148_invitation_seen_in extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("invitation", "invitation_seen_in", $this->integer(11)
            ->after("invitation_email_seen_at"));
        $this->addColumn("invitation", "invitation_seen_via", $this->string(10)
            ->after("invitation_seen_in"));

        $query = \common\models\Invitation::find()
            ->andWhere(new \yii\db\Expression("invitation_app_seen_at IS NOT NULL OR 
                invitation_email_seen_at IS NOT NULL"));

        foreach ($query->batch() as $invitations) {
            foreach ($invitations as $invitation) {

                //check whatever is low

                if ($invitation->invitation_app_seen_at) {

                    $invitation->invitation_seen_via = "app";

                    $first_seen_at = $invitation->invitation_app_seen_at;

                    if (
                        $invitation->invitation_email_seen_at &&
                        strtotime($invitation->invitation_email_seen_at) < strtotime($invitation->invitation_app_seen_at)
                    ) {
                        $first_seen_at = $invitation->invitation_email_seen_at;
                        $invitation->invitation_seen_via = "email";
                    }

                } else {

                    $invitation->invitation_seen_via = "email";

                    $first_seen_at = $invitation->invitation_email_seen_at;

                    if (
                        $invitation->invitation_app_seen_at &&
                        strtotime($invitation->invitation_app_seen_at) < strtotime($invitation->invitation_email_seen_at)
                    ) {
                        $first_seen_at = $invitation->invitation_app_seen_at;
                        $invitation->invitation_seen_via = "app";
                    }
                }

                $invitation->invitation_seen_in = strtotime($first_seen_at) - strtotime($invitation->invitation_created_at);

                $invitation->save(false);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240728_162148_invitation_seen_in cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240728_162148_invitation_seen_in cannot be reverted.\n";

        return false;
    }
    */
}
