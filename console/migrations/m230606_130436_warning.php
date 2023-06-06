<?php

use yii\db\Migration;

/**
 * Class m230606_130436_warning
 */
class m230606_130436_warning extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%candidate_warning}}', [
            'warning_id' => $this->primaryKey(),
            'candidate_id' => $this->integer (11),
            'title' => $this->string(100)->defaultValue ('Not appearing for interview'),
            'message' => $this->text()->notNull(),
            'created_by' => $this->integer(11),
            'updated_by' => $this->integer(11),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-candidate_warning-candidate_id',
            'candidate_warning',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-candidate_warning-candidate_id',
            'candidate_warning',
            'candidate_id',
            'candidate',
            'candidate_id',
            'SET NULL'
        );

        // creates index for column `created_by`
        $this->createIndex(
            'idx-candidate_warning-created_by',
            'candidate_warning',
            'created_by'
        );

        // add foreign key for table `created_by`
        $this->addForeignKey(
            'fk-candidate_warning-created_by',
            'candidate_warning',
            'created_by',
            'staff',
            'staff_id',
            'SET NULL'
        );

        // creates index for column `updated_by`
        $this->createIndex(
            'idx-candidate_warning-updated_by',
            'candidate_warning',
            'updated_by'
        );

        // add foreign key for table `updated_by`
        $this->addForeignKey(
            'fk-candidate_warning-updated_by',
            'candidate_warning',
            'updated_by',
            'staff',
            'staff_id',
            'SET NULL'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230606_130436_warning cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230606_130436_warning cannot be reverted.\n";

        return false;
    }
    */
}
