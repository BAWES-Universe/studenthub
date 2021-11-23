<?php

use yii\db\Migration;

/**
 * Class m211123_120542_candidate_time
 */
class m211123_120542_candidate_time extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('candidate', 'candidate_preferred_time', $this->string ()
            ->comment('preferred time to contact')->after ('candidate_committed'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211123_120542_candidate_time cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211123_120542_candidate_time cannot be reverted.\n";

        return false;
    }
    */
}
