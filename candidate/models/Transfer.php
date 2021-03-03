<?php
namespace candidate\models;

use Yii;


/**
 * This is the model class for table "Transfer".
 * It extends from \common\models\Transfer but with custom functionality for this application module
 */
class Transfer extends \common\models\Transfer {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['company_id'],$fields['total'],$fields['parent_transfer_id'], $fields['deleted']);

        // Update Datetime output
        $fields['transfer_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->transfer_created_at);
        };

        $fields['transfer_updated_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->transfer_updated_at);
        };

        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * Get all TransferCandidate links under this transfer
     * which include hours worked, hourly rate, etc
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferCandidates()
     * @param string $modelClass
     * @return \admin\models\Transfer|\yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }

    /**
     * Get all invoices belonging to this transfer and its children transfers
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\common\models\Invoice")
    {
        return parent::getInvoices($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransfers($modelClass = "\candidate\models\Transfer")
    {
        return parent::getChildTransfers($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\common\models\Invoice")
    {
        return parent::getChildTransferInvoices($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getChildTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\candidate\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\candidate\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Get all unpaid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return \common\models\Transfer|\yii\db\ActiveQuery
     */
    public function getUnPaidTransferCandidates($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getUnPaidTransferCandidates($modelClass);
    }

    /**
     * Get all paid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getPaidTransferCandidates($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransfer($modelClass = "\candidate\models\Transfer")
    {
        return parent::getParentTransfer($modelClass);
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransferCandidates($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getParentTransferCandidates($modelClass);
    }
}

