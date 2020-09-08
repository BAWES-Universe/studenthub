<?php

use yii\db\Migration;

/**
 * Class m200907_135723_candidate_video
 */
class m200907_135723_candidate_video extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'candidate_video', $this->string()->after('candidate_personal_photo')->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200907_135723_candidate_video cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200907_135723_candidate_video cannot be reverted.\n";

        return false;
    }
    */
}
