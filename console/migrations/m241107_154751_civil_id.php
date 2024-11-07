<?php

use yii\db\Migration;

/**
 * Class m241107_154751_civil_id
 */
class m241107_154751_civil_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex("idx-candidate-candidate_civil_id", "candidate");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241107_154751_civil_id cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241107_154751_civil_id cannot be reverted.\n";

        return false;
    }
    */
}
