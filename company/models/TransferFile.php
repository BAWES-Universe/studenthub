<?php

namespace company\models;


class TransferFile extends \common\models\TransferFile
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }
}