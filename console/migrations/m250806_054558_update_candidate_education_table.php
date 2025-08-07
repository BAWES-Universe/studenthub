<?php

use yii\db\Migration;

/**
 * Class m250806_054558_update_candidate_education_table
 */
class m250806_054558_update_candidate_education_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add enum-like column `education_type` using ENUM
        $this->addColumn('candidate_education', 'education_type', "ENUM('standard', 'custom_university', 'studying_abroad', 'not_studying') NOT NULL DEFAULT 'standard'");

        // Add nullable custom institution name column
        $this->addColumn('candidate_education', 'custom_institution_name', $this->string(255)->null()->after('education_type'));
        
        // Alter university_id to allow NULLs
        $this->alterColumn(
            'candidate_education',
            'university_id',
            $this->integer()->null()->defaultValue(null)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert the changes
        $this->dropColumn('candidate_education', 'custom_institution_name');
        $this->dropColumn('candidate_education', 'education_type');
        
        $this->alterColumn(
            'candidate_education',
            'university_id',
            $this->integer()->notNull()
        );
    }
}
