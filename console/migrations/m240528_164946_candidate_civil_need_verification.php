<?php

use yii\db\Migration;

/**
 * Class m240528_164946_candidate_civil_need_verification
 */
class m240528_164946_candidate_civil_need_verification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate", "candidate_civil_need_verification",
            $this->boolean()->defaultValue(false)->after("candidate_civil_photo_back"));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240528_164946_candidate_civil_need_verification cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240528_164946_candidate_civil_need_verification cannot be reverted.\n";

        return false;
    }
    */
}
