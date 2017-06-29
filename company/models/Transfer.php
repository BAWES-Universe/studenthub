<?php
namespace company\models;

use Yii;
use company\models\TransferCandidate;

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

    public function getChildTransferInvoices($modelClass = "\company\models\Invoice")
    {
        return parent::getChildTransferInvoices($modelClass);
    }

    public function getParentTransfer($modelClass = "\company\models\Transfer")
    {
        return parent::getParentTransfer($modelClass);
    }

    public function getChildTransfers($modelClass = "\company\models\Transfer")
    {
        return parent::getChildTransfers($modelClass);
    }
}

