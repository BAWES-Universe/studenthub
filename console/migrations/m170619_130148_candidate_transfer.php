<?php

use yii\db\Migration;

class m170619_130148_candidate_transfer extends Migration
{
    public function up()
    {
        $this->renameTable('transfer_candidates', 'transfer_candidate');
    }
}
