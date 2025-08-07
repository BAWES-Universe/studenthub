<?php

use yii\db\Migration;

/**
 * Class m250806_110820_add_custom_major_to_candidate_educations
 */
class m250806_110820_add_custom_major_to_candidate_educations extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate_education', 'custom_major', $this->string(255)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('candidate_education', 'custom_major');
    }
}
