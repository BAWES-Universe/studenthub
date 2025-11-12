<?php

namespace console\controllers;

use common\models\Fulltimer;
use Yii;
use yii\helpers\Console;
use common\models\Candidate;
use common\models\Major;


/**
 * Meilisearch console commands
 */
class MeilisearchController extends \yii\console\Controller {

    /**
     * Sync all data to Meilisearch (candidates and fulltimers)
     * Usage: ./yii meilisearch/sync
     */
    public function actionSync() {
        $this->stdout("Starting Meilisearch sync...\n", Console::FG_YELLOW);
        
        // Initialize indexes first
        $this->stdout("\n=== Initializing Indexes ===\n", Console::FG_CYAN);
        $this->actionInit();
        
        // Sync candidates
        $this->stdout("\n=== Syncing Candidates ===\n", Console::FG_CYAN);
        $candidateCount = Candidate::syncToMeilisearch('all');
        $this->stdout("✓ {$candidateCount} candidates synchronized.\n", Console::FG_GREEN);
        
        // Sync fulltimers
        $this->stdout("\n=== Syncing Fulltimers ===\n", Console::FG_CYAN);
        $fulltimerCount = Fulltimer::syncToMeilisearch('all');
        $this->stdout("✓ {$fulltimerCount} fulltimers synchronized.\n", Console::FG_GREEN);
        
        // Sync majors (if configured)
        $majorCount = 0;
        if (!empty(Yii::$app->params['meilisearch_major_index'])) {
            $this->stdout("\n=== Syncing Majors ===\n", Console::FG_CYAN);
            $majorCount = Major::syncToMeilisearch();
            $this->stdout("✓ {$majorCount} majors synchronized.\n", Console::FG_GREEN);
        }
        
        $this->stdout("\n=== Sync Complete ===\n", Console::FG_GREEN);
        $summary = "Total: {$candidateCount} candidates, {$fulltimerCount} fulltimers";
        if ($majorCount > 0) {
            $summary .= ", {$majorCount} majors";
        }
        $this->stdout("{$summary}\n", Console::FG_GREEN);
        
        return 0;
    }
    
    /**
     * Initialize Meilisearch indexes with settings
     * Usage: ./yii meilisearch/init
     */
    public function actionInit() {
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
                try {
                    $index = $client->getIndex($candidateIndex);
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
                
                $this->stdout("✓ Candidate index initialized successfully.\n", Console::FG_GREEN);
            } catch (\Exception $e) {
                $this->stdout("✗ Error initializing candidate index: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }
        
        // Initialize fulltimer index
        if (!empty(Yii::$app->params['meilisearch_fulltimer_index'])) {
            $fulltimerIndex = Yii::$app->params['meilisearch_fulltimer_index'];
            $this->stdout("Initializing fulltimer index: {$fulltimerIndex}\n", Console::FG_YELLOW);
            
            try {
                // Check if index exists, create if it doesn't
                try {
                    $index = $client->getIndex($fulltimerIndex);
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
                
                $this->stdout("✓ Fulltimer index initialized successfully.\n", Console::FG_GREEN);
            } catch (\Exception $e) {
                $this->stdout("✗ Error initializing fulltimer index: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }
        
        // Initialize major index (if configured)
        if (!empty(Yii::$app->params['meilisearch_major_index'])) {
            $majorIndex = Yii::$app->params['meilisearch_major_index'];
            $this->stdout("Initializing major index: {$majorIndex}\n", Console::FG_YELLOW);
            
            try {
                // Check if index exists, create if it doesn't
                try {
                    $index = $client->getIndex($majorIndex);
                } catch (\Exception $e) {
                    // Index doesn't exist, create it
                    $client->createIndex($majorIndex, ['primaryKey' => 'major_uuid']);
                    $index = $client->getIndex($majorIndex);
                }
                
                // Configure searchable attributes
                $index->updateSearchableAttributes([
                    'major_name_en',
                    'major_name_ar',
                ]);
                
                // Configure filterable attributes
                $index->updateFilterableAttributes([
                    'data_source',
                    'major_created_at',
                    'major_updated_at',
                ]);
                
                // Configure sortable attributes
                $index->updateSortableAttributes([
                    'major_created_at',
                    'major_updated_at',
                ]);
                
                $this->stdout("✓ Major index initialized successfully.\n", Console::FG_GREEN);
            } catch (\Exception $e) {
                $this->stdout("✗ Error initializing major index: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }
        
        $this->stdout("Meilisearch indexes initialization completed.\n", Console::FG_GREEN);
        return 0;
    }
}
