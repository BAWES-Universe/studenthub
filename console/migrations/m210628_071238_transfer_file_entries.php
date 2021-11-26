<?php

use yii\db\Migration;


/**
 * Class m210628_071238_transfer_file_entries
 */
class m210628_071238_transfer_file_entries extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //get all transfer files

        /*$transfer_files = \common\models\TransferFile::find ()
            ->andWhere('transfer_file_id NOT IN (select DISTINCT(transfer_file_id) from transfer_file_entry)');

        foreach ($transfer_files->each (1) as $transfer_file) {

            $transaction = Yii::$app->db->beginTransaction ();

            if(!$transfer_file->populateEntries()) {
                $transaction->rollBack ();
                throw new \yii\web\BadRequestHttpException('Error populating entries for transfer file #' . $transfer_file->transfer_file_id);
            }

            $transaction->commit ();
        }*/
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
        echo "m210628_071238_transfer_file_entries cannot be reverted.\n";

        return false;
    }
    */
}
