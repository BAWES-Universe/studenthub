<?php

use yii\db\Migration;

class m170227_122635_company_transfer extends Migration
{
    public function up()
    {
        $this->createTable('transfer', [
            'transfer_id' => $this->primaryKey(),
            'company_id' => $this->integer(),
            'transfer_status' => $this->smallInteger()->notNull()->defaultValue(10),
            'transfer_created_at' => $this->datetime()->notNull(),
            'transfer_updated_at' => $this->datetime()->notNull()
        ]);

        $this->createIndex(
            'idx-transfer-company_id',
            'transfer',
            'company_id'
        );
        
        $this->addForeignKey(
            'fk-transfer-company_id',
            'transfer',
            'company_id',
            'company',
            'company_id',
            'CASCADE'
        );

        $this->createTable('transfer_candidates', [
            'tc_id' => $this->primaryKey(),
            'transfer_id' => $this->integer(),
            'candidate_id' => $this->integer(),
            'hours' => $this->decimal(10, 2),
            'bonus' => $this->decimal(10, 3),
            'tc_created_at' => $this->datetime()->notNull(),
            'tc_updated_at' => $this->datetime()->notNull()
        ]);

        $this->createIndex(
            'idx-tc-transfer_id',
            'transfer_candidates',
            'transfer_id'
        );

        $this->addForeignKey(
            'fk-tc-transfer_id',
            'transfer_candidates',
            'transfer_id',
            'transfer',
            'transfer_id',
            'CASCADE'
        );

        $this->createIndex(
            'idx-tc-candidate_id',
            'transfer_candidates',
            'candidate_id'
        );
        
        $this->addForeignKey(
            'fk-tc-candidate_id',
            'transfer_candidates',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );
    }

    public function down()
    {
        echo "m170227_122635_company_transfer cannot be reverted.\n";

        return false;
    }
}
