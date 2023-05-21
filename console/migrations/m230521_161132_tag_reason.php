<?php

use yii\db\Migration;

/**
 * Class m230521_161132_tag_reason
 */
class m230521_161132_tag_reason extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('candidate_tag', 'reason',
            $this->text ()
                ->after ('tag'));

        $this->addColumn ('candidate_tag', 'created_by',
            $this->integer (11)
                ->after ('created_at'));

        $this->createIndex(
            'idx-candidate_tag-created_by',
            'candidate_tag',
            'created_by'
        );

        $this->addForeignKey(
            'fk-candidate_tag-created_by',
            'candidate_tag',
            'created_by',
            'staff',
            'staff_id',
            'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230521_161132_tag_reason cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230521_161132_tag_reason cannot be reverted.\n";

        return false;
    }
    */
}
