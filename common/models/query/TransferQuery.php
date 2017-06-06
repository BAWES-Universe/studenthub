<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[Country]].
 *
 */
class TransferQuery extends \yii\db\ActiveQuery
{

    public function all($db = null)
    {
        return parent::all($db);
    }

    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Filter parent transfer
     */
    public function parentTransfers()
    {
        return $this->andWhere('parent_transfer_id IS NULL');
    }

    /**
     * Field require on listing
     */
    public function selectedFields()
    {
        return $this->select([
            '{{%transfer}}.*',
            '{{%company}}.company_name',
            '{{%company}}.company_email'
        ]);
    }

    public function companyJoin()
    {
        return $this->leftJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id');
    }

}
	