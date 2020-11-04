<?php

use common\models\Transfer;
use yii\db\Migration;
use common\models\Invoice;
use common\models\TransferCandidate;
/**
 * Class m201103_094142_soft_delete_pending_child_transfer
 */
class m201103_094142_soft_delete_pending_child_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        #https://www.pivotaltracker.com/story/show/175456763
        // creating this migration because we have several candidate transfer which are active but
        // there parent transfer are deleted

        $transfer = Yii::$app->db->createCommand("select * from transfer where deleted = 1")->queryAll();

        if(count($transfer)> 0) {
            foreach ($transfer as $model) {
                $children = Transfer::find()->filterParent($model['transfer_id'])->all();

                //delete data for each child

                foreach ($children as $key => $child)
                {
                    Invoice::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
                    Transfer::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
                    TransferCandidate::updateAll(['deleted' => 1], ['transfer_id' => $model['transfer_id']]);
                }

                //delete data for main transfer
                Invoice::updateAll(['deleted' => 1], ['transfer_id' => $model['transfer_id']]);
                Transfer::updateAll(['deleted' => 1], ['transfer_id' => $model['transfer_id']]);
                TransferCandidate::updateAll(['deleted' => 1], ['transfer_id' => $model['transfer_id']]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201103_094142_soft_delete_pending_child_transfer cannot be reverted.\n";

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201103_094142_soft_delete_pending_child_transfer cannot be reverted.\n";

        return false;
    }
    */
}
