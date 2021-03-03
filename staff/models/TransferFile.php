<?php


namespace staff\models;


class TransferFile extends \common\models\TransferFile
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\staff\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }
}