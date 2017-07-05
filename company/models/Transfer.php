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
     * @param $sub_companies
     * @param $model
     */
    public static function generateSubCompanyTransfer($sub_companies, $model) {

        if ($sub_companies) {
            foreach ($sub_companies as $key => $company) {

                //move transfer to transfer
                $transfer = Transfer::findOne([
                    'parent_transfer_id' => $model->transfer_id,
                    'company_id' => $company['company_id']
                ]);

                if (!$transfer) {
                    $transfer = new Transfer;
                    $transfer->attributes = $model->attributes;
                    $transfer->parent_transfer_id = $model->transfer_id;
                    $transfer->company_id = $company['company_id'];
                    $transfer->transfer_status = Transfer::STATUS_LOCK;
                    $transfer->save(false);
                }

                $total = $company_total = 0;

                //remove old candidates if exists
                TransferCandidate::deleteAll(['transfer_id' => $transfer->transfer_id]);

                // transfer candidate for current company

                $candidates = TransferCandidate::find()
                    ->candidatesByTransfer($model->transfer_id)
                    ->filterCompanyId($company['company_id'])
                    ->asArray()
                    ->all();

                foreach ($candidates as $key_one => $value) {
                    $total += $value['bonus'] + ($value['hours'] * $value['candidate_hourly_rate']) + Yii::$app->params['transfer_cost'];

                    $company_total += $value['bonus'] + ($value['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
                }

                // Save total in transfer
                $transfer->company_total = $company_total;
                $transfer->total = $total;
                $transfer->save();

                // Generate invoice for each transfer
                Transfer::generateTransferInvoice($transfer);

            }
        }
    }

    /**
     * @param $model
     * @return bool
     */
    public static function generateTransferInvoice($model) {
        $invoice = Invoice::findOne(['transfer_id' => $model->transfer_id]);

        if(!$invoice) {
            $invoice = new Invoice;
            $invoice->transfer_id = $model->transfer_id;
            $invoice->invoice_date = date('Y-m-d');
            $invoice->invoice_status = 'unpaid';
            return $invoice->save();
        }
        return false; // in case if invoice exist
    }

    /**
     * @param $model
     * @param $company
     * @return bool
     */
    public static function generateEachCompanyTransfer($model,$company) {

        $sub_companies = TransferCandidate::find()
            ->candidatesByTransfer($model->transfer_id)
            ->groupByCompany($model->company_id)
            ->all();

        // condition to check if current company has existing sub companies.
        $sub_companies = (
            $sub_companies &&
            (isset($company->subCompanies)) &&
            count($company->subCompanies)>0
        ) ? $sub_companies : false;

        /**
         * generate invoice for main transfer if no sub companies else generate
         * invoice for sub companies
         */
        if (!$sub_companies) {
            Transfer::generateTransferInvoice($model);
        }

        /**
         * if transfer initiated by parent company split it for each
         * sub companies
         */
        if ($sub_companies) {
            Transfer::generateSubCompanyTransfer($sub_companies, $model);
        }
        return true;
    }

    /**
     * @param $model
     * @return bool
     */
    public static function deleteChildTransfer($model) {

        $children = Transfer::find()->filterParent($model->transfer_id)->all();

        //delete data for each child

        foreach ($children as $key => $child)
        {
            Invoice::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
            Transfer::updateAll(['deleted' => 1], ['transfer_id' => $child->transfer_id]);
        }

        //delete data for main transfer
        Invoice::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);
        Transfer::updateAll(['deleted' => 1], ['transfer_id' => $model->transfer_id]);

        return true;
    }

    /**
     * @param $company
     * @param $candidates
     * @return array
     */
    public static function saveTransfer($company, $candidates) {

        $transaction = Yii::$app->db->beginTransaction();

        $transfer = new Transfer;
        $transfer->company_id = $company->company_id;

        if(!$transfer->save()) {
            if(isset($transfer->errors)) {
                return [
                    "operation" => "error",
                    "message" => $transfer->errors
                ];
            }

            return [
                "operation" => "error",
                "message" => "We've faced a problem creating the account, please contact us for assistance."
            ];
        }

        // Save candidate data

        $total = $company_total = 0;

        foreach ($candidates as $key => $value) {

            if(empty($value['bonus']))
                $value['bonus'] = 0;

            if(empty($value['hours']))
                $value['hours'] = 0;

            $candidate = Candidate::findOne($value['candidate_id']);
            if(!$candidate)
            {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate not found, please contact us for assistance"
                ];
            }

            $response = TransferCandidate::saveCandidateTransfer($candidate, $transfer, $value);
            if ($response['operation'] == "error") {
                $transaction->rollBack();
                return $response; // error will be respond back
            } else {
                $total += (int)$response['total'];
                $company_total += (int)$response['company_total'];
            }
        }

        if($total <= 0) {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => "Transfer total is zero. Please input hours worked."
            ];
        }

        $transfer->company_total = $company_total;
        $transfer->total = $total;
        $transfer->save();

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Transfer created.",
            "transfer_id" => $transfer->transfer_id
        ];
    }


    public static function updateTransfer($company,$id,$candidates) {

        $model = Transfer::find()
            ->filterTransfer($id)
            ->filterCurrentCompany($company)
            ->one();

        $new_transfer_id = $new_invoice_id = [];

        //Old Child Transfers
        $old_child_transfers = $model->childTransfers;
        //Old Invoices
        $old_invoices = $model->invoices;

        $transaction = Yii::$app->db->beginTransaction();

        //remove old candidates

        TransferCandidate::deleteAll(['transfer_id' => $model->transfer_id]);

        //save candidates

        $total = $company_total = 0;

        foreach($candidates as $key => $value)
        {
            if(empty($value['bonus']))
                $value['bonus'] = 0;

            if(empty($value['hours']))
                $value['hours'] = 0;

            //candiate hourly_rate

            $candidate = Candidate::findOne($value['candidate_id']);

            if(!$candidate)
            {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => "Candidate not found"
                ];
            }

            // save candidate transfer
            $response = TransferCandidate::saveCandidateTransfer($candidate, $model, $value);
            if ($response['operation'] == "error") {
                $transaction->rollBack();
                return $response; // error will be respond back
            } else {
                $total += (int)$response['total'];
                $company_total += (int)$response['company_total'];
            }
        }

        $model->company_total = $company_total;
        $model->total = $total;

        if($total <= 0)
        {
            $transaction->rollBack();

            return [
                "operation" => "error",
                "message" => "transfer total can not be zero!"
            ];
        }

        $model->save();

        $new_transfer_id[] = $model->transfer_id;

        //update child transfers

        //select distinct company and update transfer for each company if already added else create new

        $sub_companies = TransferCandidate::find()
            ->candidatesByTransfer($model->transfer_id)
            ->groupByCompany($model->company_id)
            ->asArray()
            ->all();

        /**
         * generate invoice for main transfer if no sub companies else generate
         * invoice for each sub companies
         */
        if(!$sub_companies && $model->transfer_status != Transfer::STATUS_INITIATED)
        {
            $new_invoice_id[] = $model->generateInvoice();
        }

        foreach ($sub_companies as $key => $sub_company) {

            //move transfer to transfer
            $transfer = Transfer::find()
                ->filterCompanyId($sub_company['company_id'])
                ->filterParent($model->transfer_id)
                ->one();

            if(empty($transfer)) {
                $transfer = new Transfer;
                $transfer->attributes = $model->attributes;
                $transfer->parent_transfer_id = $model->transfer_id;
                $transfer->company_id = $sub_company['company_id'];
            }

            if(!$transfer->save(false))
            {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $transfer->getErrors()
                ];
            }

            $total = $company_total = 0;

            // Remove old candidate id exists
            TransferCandidate::deleteAll(['transfer_id' => $transfer->transfer_id]);

            // Transfer candidate for current company
            $candidates = TransferCandidate::find()
                ->candidatesByTransfer($model->transfer_id)
                ->filterCompanyId($sub_company['company_id'])
                ->all();

            foreach ($candidates as $key => $value)
            {
                $total += $value['bonus'] + ($value['hours'] * $value['candidate_hourly_rate']) + Yii::$app->params['transfer_cost'];
                $company_total += $value['bonus'] + ($value['hours'] * Yii::$app->params['candidate_max_hourly_rate']);
            }

            // Save total in transfer
            $transfer->company_total = $company_total;
            $transfer->total = $total;
            if(!$transfer->save())
            {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $transfer->getErrors()
                ];
            }

            // Generate invoice for each transfer
            if($model->transfer_status != Transfer::STATUS_INITIATED) {
                $new_invoice_id[] = $transfer->generateInvoice();
            }

            $new_transfer_id[] = $transfer->transfer_id;
        }

        //remove extra transfers
        foreach ($old_child_transfers as $key => $value)
        {
            if(!in_array($value->transfer_id, $new_transfer_id))
            {
                //remove transfer data
                //Keep hard delete here as on recover of actual transfer we got required data
                TransferCandidate::deleteAll(['transfer_id' => $value->transfer_id]);
                Transfer::updateAll(['deleted' => 1], ['transfer_id' => $value->transfer_id]);
            }
        }

        //remove extra invoices
        foreach ($old_invoices as $key => $value)
        {
            if(!in_array($value->invoice_id, $new_invoice_id))
            {
                //remove invoice
                Invoice::updateAll(['deleted' => 1], ['invoice_id' => $value->invoice_id]);
            }
        }

        $transaction->commit();

        return [
            "operation" => "success",
            "message" => "Your transfer has been updated."
        ];
    }
}

