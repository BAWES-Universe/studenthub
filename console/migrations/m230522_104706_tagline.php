<?php

use yii\db\Migration;

/**
 * Class m230522_104706_tagline
 */
class m230522_104706_tagline extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'candidate_intro', $this->text()->after('candidate_objective'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230522_104706_tagline cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230522_104706_tagline cannot be reverted.\n";

        return false;
    }
    */
}
