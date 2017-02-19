<?php

use yii\db\Migration;

class m170219_055954_missing_candidate_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('candidate', 'candidate_name_ar', $this->string()->notNull()->after('candidate_name'));
        $this->addColumn('candidate', 'candidate_birth_date', $this->date()->notNull()->after('candidate_email'));
        $this->addColumn('candidate', 'candidate_civil_expiry_date', $this->date()->notNull()->after('candidate_civil_id'));
        $this->addColumn('candidate', 'candidate_civil_photo_front', $this->date()->after('candidate_civil_expiry_date'));
        $this->addColumn('candidate', 'candidate_civil_photo_back', $this->date()->after('candidate_civil_photo_front'));
        $this->addColumn('candidate', 'candidate_hourly_rate', $this->decimal(7,3)->notNull()->after('candidate_civil_photo_back'));
    }

    public function safeDown()
    {
        $this->dropColumn('candidate', 'candidate_hourly_rate');
        $this->dropColumn('candidate', 'candidate_civil_photo_back');
        $this->dropColumn('candidate', 'candidate_civil_photo_front');
        $this->dropColumn('candidate', 'candidate_civil_expiry_date');
        $this->dropColumn('candidate', 'candidate_birth_date');
        $this->dropColumn('candidate', 'candidate_name_ar');
    }
}
