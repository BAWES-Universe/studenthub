<?php

use yii\db\Migration;


/**
 * Class m200817_150620_invalid_transfer
 */
class m200817_150620_invalid_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        company\models\TransferCandidate::deleteAll([
            "AND",
            new yii\db\Expression('hours IS NULL OR hours = 0'),
            new yii\db\Expression('bonus IS NULL OR bonus = 0')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200817_150620_invalid_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200817_150620_invalid_transfer cannot be reverted.\n";

        return false;
    }
    */
}
