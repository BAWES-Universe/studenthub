<?php

namespace admin\models;


class TransferFile extends \common\models\TransferFile
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }
}
