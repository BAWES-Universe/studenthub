<?php

use yii\db\Migration;

class m170502_143647_candidate_ID extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // Owner
        $this->createTable('candidate_id_card', [
            'id' => $this->primaryKey(),
            'candidate_id' => $this->integer(11),
            'expiry_date' => $this->date(),
            'created_at' => $this->datetime(),
            'updated_at' => $this->datetime()
        ], $tableOptions);

        $this->createIndex(
            'idx-candidate_id_card-candidate_id',
            'candidate_id_card',
            'candidate_id'
        );
        
        $this->addForeignKey(
            'fk-candidate_id_card-candidate_id',
            'candidate_id_card',
            'candidate_id',
            'candidate',
            'candidate_id',
            'SET NULL'
        );
    }
}
