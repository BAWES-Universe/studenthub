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
     * Sync all data to Meilisearch (initializes indexes and syncs data)
     * Usage: ./yii meilisearch/sync
     * 
     * This command automatically:
     * - Initializes indexes (creates if missing, updates settings if existing)
     * - Syncs all data (candidates, fulltimers, majors)
     * 
     * Note: For large datasets, increase PHP memory limit:
     * php -d memory_limit=512M ./yii meilisearch/sync
     */
    public function actionSync() {
        // Increase memory limit for large syncs
        ini_set('memory_limit', '512M');
        $this->stdout("Starting Meilisearch sync...\n", Console::FG_YELLOW);
        
        if (!isset(Yii::$app->meilisearch)) {
            $this->stdout("Meilisearch is not configured.\n", Console::FG_RED);
            return 1;
        }
        
        $client = Yii::$app->meilisearch->getClient();
        
        // Initialize indexes first
        $this->stdout("\n=== Initializing Indexes ===\n", Console::FG_CYAN);
        
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
                
                // Configure searchable attributes (priority order for relevance)
                $index->updateSearchableAttributes([
                    'candidate_name',              // Highest priority - exact name matches
                    'candidate_name_ar',
                    'candidate_civil_id',          // Search by civil ID
                    'candidate_email',             // Search by email
                    'candidate_phone',             // Search by phone
                    'candidate_objective',
                    'candidateSkills.skill',       // Array of skills
                    'skills_text',                 // Flattened skills text
                    'candidateEducations.major.major_name_en',
                    'candidateEducations.major.major_name_ar',
                    'education_majors_text',       // Flattened majors text
                    'candidateExperiences.experience',
                    'candidateExperiences.employer',
                    'experience_text',             // Flattened experience text
                    'university.university_name_en',
                    'university.university_name_ar',
                    'currentLocations.en',         // Array
                    'currentLocations.ar',         // Array
                ]);
                
                // Configure filterable attributes (for facets with real-time counts)
                $index->updateFilterableAttributes([
                    'candidate_gender',                    // Male, Female, Other
                    'candidate_driving_license',           // 1, 2
                    'candidate_language_pref',             // en, ar
                    'candidate_job_search_status',         // 0, 1, 2
                    'candidate_committed',                 // Yes, No
                    'candidate_mom_kuwaiti',               // 1, 2
                    'approved',                            // 0, 1
                    'assigned',                           // 0, 1
                    'have_video',                         // Yes, No
                    'have_resume',                        // Yes, No
                    'isProfileCompleted',                 // boolean
                    'currency_code',                      // string
                    'university.university_id',            // integer (with university_name for display)
                    'country.country_id',                 // integer (with country_name for display)
                    'bank.bank_id',                       // integer (with bank_name for display)
                    'candidate_birth_timestamp',          // integer (for age filtering)
                    'age',                                // computed age
                    'candidate_created_at_timestamp',     // integer
                    'candidate_updated_at_timestamp',     // integer
                    'candidateEducations.graduation_year', // integer
                    'candidateEducations.degree.degree_name_en', // string
                    'candidateEducations.major.major_name_en',   // string (field of study)
                    'candidateExperiences.experience',    // string
                    'store.store_id',                     // integer (for assigned location)
                    'store.store_name',                   // string (for display in facets)
                    'candidateIdCard_status',             // Expired, Not Expired
                    '_geo',                               // for proximity search
                ]);
                
                // Configure sortable attributes
                $index->updateSortableAttributes([
                    'candidate_created_at_timestamp',
                    'candidate_updated_at_timestamp',
                    'candidate_birth_timestamp',
                    'age',
                    '_geo',                               // for distance sorting
                ]);
                
                // Configure ranking rules (priority order)
                $index->updateRankingRules([
                    'words',                              // Exact word matches first
                    'typo',                               // Typo tolerance
                    'proximity',                          // Word proximity in text
                    'attribute',                          // Attribute priority (name > objective > skills)
                    'sort',                               // Custom sorting
                    'exactness',                          // Exact matches
                ]);
                
                // Configure typo tolerance
                $index->updateTypoTolerance([
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 4,
                        'twoTypos' => 8,
                    ],
                ]);
                
                // Configure synonyms (common variations)
                $synonyms = [
                    // Skill variations
                    'communication' => ['communication', 'communications', 'communicate'],
                    'customer service' => ['customer service', 'customer support', 'client service'],
                    'sales' => ['sales', 'selling', 'retail'],
                    // University name variations
                    'kuwait university' => ['kuwait university', 'ku university', 'ku'],
                    'arab open university' => ['arab open university', 'aou', 'arab open'],
                    // Location variations
                    'kuwait' => ['kuwait', 'kw'],
                ];
                $index->updateSynonyms($synonyms);
                
                // Configure stop words (Arabic and English)
                $stopWords = [
                    // English stop words
                    'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
                    'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
                    'to', 'was', 'will', 'with',
                    // Arabic stop words
                    'في', 'من', 'إلى', 'على', 'هذا', 'هذه', 'ذلك', 'تلك', 'التي', 'الذي',
                    'كان', 'كانت', 'يكون', 'تكون', 'كانوا', 'يكونون',
                ];
                $index->updateStopWords($stopWords);
                
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
}
