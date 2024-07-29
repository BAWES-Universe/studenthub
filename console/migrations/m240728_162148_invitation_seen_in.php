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
        $columnInvitationSeenIn = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('invitation')
            ->getColumn('invitation_seen_in');

        if (!$columnInvitationSeenIn) {
            $this->addColumn("invitation", "invitation_seen_in", $this->integer(11)
                ->after("invitation_email_seen_at"));
        }

        $columnInvitationSeenVia = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('invitation')
            ->getColumn('invitation_seen_via');

        if(!$columnInvitationSeenVia) {
            $this->addColumn("invitation", "invitation_seen_via", $this->string(10)
                ->after("invitation_seen_in"));
        }

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

                //$invitation->save(false);

                \common\models\Invitation::updateAll([
                    "invitation_seen_in" => $invitation->invitation_seen_in,
                    "invitation_seen_via" => $invitation->invitation_seen_via
                ], [
                    "invitation_uuid" => $invitation->invitation_uuid
                ]);
            }
        }
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
        echo "m240728_162148_invitation_seen_in cannot be reverted.\n";

        return false;
    }
    */
}
