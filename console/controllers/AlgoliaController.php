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
    public function actionIndex($entity, $type = "all") {
        switch ($entity) {
            case 'candidate':
                $count = Candidate::synchWithAlgolia($type);
                $this->stdout(PHP_EOL . $count . " Candidate synchronized. \n", Console::FG_RED, Console::BOLD);
                break;
            case 'fulltimer':
                $count = Fulltimer::synchWithAlgolia($type);
                $this->stdout(PHP_EOL . $count . " Fulltimer synchronized. \n", Console::FG_RED, Console::BOLD);
                break;
            default:
                break;
        }
    }
    
    /**
     * Initialize Meilisearch indexes with settings
     */
    public function actionInitMeilisearch() {
        if (!isset(Yii::$app->meilisearch)) {
            $this->stdout("Meilisearch is not configured.\n", Console::FG_RED);
            return 1;
        }
        
        $client = Yii::$app->meilisearch->getClient();
        
        // Initialize candidate index
        if (!empty(Yii::$app->params['meilisearch_candidate_index'])) {
            $candidateIndex = Yii::$app->params['meilisearch_candidate_index'];
            $this->stdout("Initializing candidate index: {$candidateIndex}\n", Console::FG_YELLOW);
            
            try {
                // Check if index exists, create if it doesn't
                $indexExists = false;
                try {
                    $index = $client->getIndex($candidateIndex);
                    $indexExists = true;
                } catch (\Exception $e) {
                    // Index doesn't exist, create it
                    $client->createIndex($candidateIndex, ['primaryKey' => 'candidate_id']);
                    $index = $client->getIndex($candidateIndex);
                }
                
                // Configure searchable attributes
                $index->updateSearchableAttributes([
                    'candidate_name',
                    'candidate_name_ar',
                    'candidate_objective',
                    'candidate_email',
                    'candidate_phone',
                    'university.university_name',
                    'country.country_name_en',
                    'country.country_name_ar',
                    'currentLocations.en',
                    'currentLocations.ar',
                    'candidateSkills.skill',
                    'candidateEducations.major',
                    'candidateExperiences.company_name',
                    'candidateExperiences.position',
                ]);
                
                // Configure filterable attributes (for facets)
                $index->updateFilterableAttributes([
                    'candidate_gender',
                    'candidate_committed',
                    'have_video',
                    'have_resume',
                    'candidate_driving_license',
                    'candidate_language_pref',
                    'candidate_job_search_status',
                    'approved',
                    'candidate_mom_kuwaiti',
                    'currency_code',
                    'isProfileCompleted',
                    'assigned',
                    'university.university_id',
                    'country.country_id',
                    'bank.bank_id',
                    'candidate_birth_timestamp',
                    'candidate_created_at_timestamp',
                    'candidate_updated_at_timestamp',
                ]);
                
                // Configure sortable attributes
                $index->updateSortableAttributes([
                    'candidate_created_at_timestamp',
                    'candidate_updated_at_timestamp',
                    'candidate_birth_timestamp',
                ]);
                
                $this->stdout("Candidate index initialized successfully.\n", Console::FG_GREEN);
            } catch (\Exception $e) {
                $this->stdout("Error initializing candidate index: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }
        
        // Initialize fulltimer index
        if (!empty(Yii::$app->params['meilisearch_fulltimer_index'])) {
            $fulltimerIndex = Yii::$app->params['meilisearch_fulltimer_index'];
            $this->stdout("Initializing fulltimer index: {$fulltimerIndex}\n", Console::FG_YELLOW);
            
            try {
                // Check if index exists, create if it doesn't
                $indexExists = false;
                try {
                    $index = $client->getIndex($fulltimerIndex);
                    $indexExists = true;
                } catch (\Exception $e) {
                    // Index doesn't exist, create it
                    $client->createIndex($fulltimerIndex, ['primaryKey' => 'fulltimer_uuid']);
                    $index = $client->getIndex($fulltimerIndex);
                }
                
                // Configure searchable attributes
                $index->updateSearchableAttributes([
                    'fulltimer_name',
                    'fulltimer_email',
                    'fulltimer_phone',
                    'university.university_name',
                    'country.country_name_en',
                    'country.country_name_ar',
                    'currentLocations.en',
                    'currentLocations.ar',
                    'fulltimerSkills.skill',
                    'fulltimerExperiences.experience',
                ]);
                
                // Configure filterable attributes (for facets)
                $index->updateFilterableAttributes([
                    'fulltimer_gender',
                    'fulltimer_employed',
                    'have_resume',
                    'fulltimer_driving_license',
                    'currency_code',
                    'nationality.nationality_id',
                    'country.country_id',
                    'university.university_id',
                    'fulltimer_birth_timestamp',
                    'fulltimer_created_timestamp',
                    'fulltimer_updated_timestamp',
                ]);
                
                // Configure sortable attributes
                $index->updateSortableAttributes([
                    'fulltimer_created_timestamp',
                    'fulltimer_updated_timestamp',
                    'fulltimer_birth_timestamp',
                ]);
                
                $this->stdout("Fulltimer index initialized successfully.\n", Console::FG_GREEN);
            } catch (\Exception $e) {
                $this->stdout("Error initializing fulltimer index: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }
        
        $this->stdout("Meilisearch indexes initialization completed.\n", Console::FG_GREEN);
        return 0;
    }
}
