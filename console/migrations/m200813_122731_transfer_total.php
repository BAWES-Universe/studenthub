<?php

use yii\db\Migration;

/**
 * Class m200813_122731_transfer_total
 */
class m200813_122731_transfer_total extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $transferFiles = Yii::$app->db->createCommand('SELECT * from transfer_file where transfer_amount = 0 OR transfer_amount IS NULL')->queryAll();
        
        foreach($transferFiles as $transferFile) {
            
            $transfer_amount = \common\models\TransferCandidate::find()
               ->select(new yii\db\Expression('SUM((candidate_hourly_rate * hours) + bonus - bonus_commission)'))
               ->filterWhere(['transfer_file_id' => $transferFile['transfer_file_id']])
               ->scalar();
            
            Yii::$app->db->createCommand('UPDATE transfer_file SET transfer_amount="'.$transfer_amount.'" WHERE transfer_file_id="'.$transferFile['transfer_file_id'].'"')->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200813_122731_transfer_total cannot be reverted.\n";

        return false;
    }
    */
}
