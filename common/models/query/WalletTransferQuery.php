<?php

namespace common\models\query;

use common\models\WalletTransfer;
use yii\db\ActiveQuery;
use yii\db\Expression;

class WalletTransferQuery extends ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        //$this->andWhere(['{{%transfer}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        //$this->andWhere(['{{%transfer}}.deleted' => 0]);
        return parent::one($db);
    }

    public function initiated()
    {
        return $this->andWhere(['{{%transfer}}.transfer_status' => WalletTransfer::STATUS_INITIATED]);//
    }

    public function payable()
    {
        return $this->andWhere(['{{%transfer}}.transfer_status' => WalletTransfer::STATUS_IN_PROGRESS]);//INITIATED
    }

    public function havingBankInfo()
    {
        return $this->andWhere(new Expression('transfer_benef_name IS NOT NULL AND transfer_benef_iban IS NOT NULL AND 
            bank_uuid IS NOT NULL'));
    }
}