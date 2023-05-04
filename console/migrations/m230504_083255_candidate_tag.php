<?php

use yii\db\Migration;

/**
 * Class m230504_083255_candidate_tag
 */
class m230504_083255_candidate_tag extends Migration
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

        $this->createTable('{{%candidate_tag}}', [
            'tag_id' => $this->primaryKey(),
            'candidate_id' => $this->integer(11),
            'tag' => $this->string(128)->notNull(),
            'deleted' => $this->smallInteger(1)->defaultValue(0)->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addForeignKey(
            'fk-candidate_tag-candidate_id',
            'candidate_tag',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230504_083255_candidate_tag cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230504_083255_candidate_tag cannot be reverted.\n";

        return false;
    }
    */
}
