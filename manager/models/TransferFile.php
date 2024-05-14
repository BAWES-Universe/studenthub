<?php

namespace manager\models;


class TransferFile extends \common\models\TransferFile
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\manager\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }
}