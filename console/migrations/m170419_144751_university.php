<?php

use yii\db\Migration;

class m170419_144751_university extends Migration
{
    public function up()
    {
        $this->createTable('university', [
            'university_id' => $this->primaryKey(),
            'university_name_en' => $this->string(100),
            'university_name_ar' => $this->string(100),
        ]);
    }

    public function down()
    {
        $this->dropTable('university');
    }
}
