<?php

use yii\db\Migration;

class m170429_161602_civil_photo_type extends Migration
{
    public function up()
    {
        $this->alterColumn("candidate", "candidate_civil_photo_front", $this->string());
        $this->alterColumn("candidate", "candidate_civil_photo_back", $this->string());
    }

    public function down()
    {
        echo "m170429_161602_civil_photo_type cannot be reverted.\n";

        return false;
    }
}
