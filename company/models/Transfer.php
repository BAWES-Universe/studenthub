<?php
namespace company\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\base\Exception;


/**
 * This is the model class for table "Transfer".
 * It extends from \common\models\Transfer but with custom functionality for this application module
 */
class Transfer extends \common\models\Transfer {

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = ['candidates', 'validateCandidates'];

        return $rules;
    }

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
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'invoices',
            'childTransfers',
            'transferCandidates',
            'childTransferInvoices',
            'childTransferCandidates'
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Get all invoices belonging to this transfer and its children transfers
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getInvoices($modelClass = "\company\models\Invoice")
    {
        return parent::getInvoices($modelClass);
    }

    /**
     * Get all TransferCandidate links under this transfer
     * which include hours worked, hourly rate, etc
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferCandidates()
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\company\models\Invoice")
    {
        return parent::getChildTransferInvoices($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransfer($modelClass = "\company\models\Transfer")
    {
        return parent::getParentTransfer($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getChildTransfers($modelClass = "\company\models\Transfer")
    {
        return parent::getChildTransfers($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getChildTransferCandidates($modelClass);
    }

    /**
     * Get all unpaid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return \common\models\Transfer|\yii\db\ActiveQuery
     */
    public function getUnPaidTransferCandidates($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getUnPaidTransferCandidates($modelClass);
    }

    /**
     * Get all paid TransferCandidate related to this transfer or its parent transfer
     * which include each employees hours worked, hourly rate, etc
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getPaidTransferCandidates($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getPaidTransferCandidates($modelClass);
    }

    /**
     * Get all TransferCandidates belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getParentTransferCandidates($modelClass = "\company\models\TransferCandidate")
    {
        return parent::getParentTransferCandidates($modelClass);
    }

}
