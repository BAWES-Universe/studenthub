<?php

use yii\db\Migration;
use yii\helpers\Console;
use common\models\Candidate;

/**
 * Class m211223_081947_transfer_api
 */
class m211223_081947_transfer_api extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if(!in_array(
            Yii::$app->params['algolia_candidate_index'], [
            'prod_candidate_public',
            'dev_candidate_public'
        ])) {
            return true; 
        }

        $this->addColumn ('candidate', 'candidate_pending_profile', $this->text ()->after ('candidate_mom_kuwaiti'));

        $candidates = Candidate::find()
            ->andWhere (['deleted' => 0]);

        $count = 0;

        $total = Candidate::find()
            ->andWhere (['deleted' => 0])
            ->count ();

        Console::startProgress(0, $total);

        foreach($candidates->batch(10) as $candidates)
        {
            $count += sizeof ($candidates);

            //Console::stdout($count . " processed  \n", Console::FG_RED, Console::NORMAL);

            foreach($candidates as $candidate)
            {
                $candidate->setScenario('updatePendingProfile');
                $candidate->save (false);
            }

            Console::updateProgress($count, $total);
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
        echo "m211223_081947_transfer_api cannot be reverted.\n";

        return false;
    }
    */
}