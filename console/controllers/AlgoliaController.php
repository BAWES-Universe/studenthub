<?php

namespace console\controllers;

use common\models\Fulltimer;
use Yii;
use yii\helpers\Console;
use common\models\Candidate;


/**
 * All Cron actions related algolia 
 */
class AlgoliaController extends \yii\console\Controller {

    /**
     * Synch selected enity 
     */
    public function actionIndex($entity) {
        switch ($entity) {
            case 'candidate':
                $count = Candidate::synchWithAlgolia();
                $this->stdout(PHP_EOL . $count . " Candidate synchronized. \n", Console::FG_RED, Console::BOLD);
                break;
            case 'fulltimer':
                $count = Fulltimer::synchWithAlgolia();
                $this->stdout(PHP_EOL . $count . " Fulltimer synchronized. \n", Console::FG_RED, Console::BOLD);
                break;
            default:
                break;
        }
    } 
}
