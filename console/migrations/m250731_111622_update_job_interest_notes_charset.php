<?php

use yii\db\Migration;

/**
 * Class m250731_111622_update_job_interest_notes_charset
 */
class m250731_111622_update_job_interest_notes_charset extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Update specific columns
        $this->execute("ALTER TABLE job_interest MODIFY notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("ALTER TABLE job_interest MODIFY notes TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
    }
}
