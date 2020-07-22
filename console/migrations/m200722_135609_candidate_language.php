<?php

use yii\db\Migration;


/**
 * Class m200722_135609_candidate_language
 */
class m200722_135609_candidate_language extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'candidate', 
            'candidate_language_pref', 
            $this->char(2)->defaultValue('en')->after('candidate_password_reset_token')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200722_135609_candidate_language cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200722_135609_candidate_language cannot be reverted.\n";

        return false;
    }
    */
}
