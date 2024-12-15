<?php

use yii\db\Migration;

/**
 * Class m241127_162409_experience
 */
class m241127_162409_experience extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_experience", "employer", $this->string());
        $this->addColumn("candidate_experience", "start_year", $this->smallInteger(5));
        $this->addColumn("candidate_experience", "end_year", $this->smallInteger(5));
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
        echo "m241127_162409_experience cannot be reverted.\n";

        return false;
    }
    */
}
