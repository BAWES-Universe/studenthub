<?php

namespace admin\models;

use Yii;

/**
 * Class TransferCandidate
 * @package admin\models
 */
class TransferCandidate extends \common\models\TransferCandidate
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

    	$fields['candidate'] = function($model) {
    		return $model->candidate;
    	};

        $fields['status'] = function($model){
            return ($model->paid) ? 'Paid' : 'Unpaid';
        };

        $fields['total'] = function($model) {
            return ($model->candidate_hourly_rate * $model->hours) + $model->bonus;
        };

        $fields['tc_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_created_at, "long");
        };

        $fields['tc_updated_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->tc_updated_at, "long");
        };

    	return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\admin\models\Store")
    {
        return parent::getStore($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransfer($modelClass = "\admin\models\Transfer")
    {
        return parent::getTransfer($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice($modelClass = "\admin\models\Invoice")
    {
        return parent::getInvoice($modelClass);
    }

    /**
     * mark transfer candidate as unpaid
     * also mark transfer from complete to
     * progress in case if its completed
     * @param $tc_id
     * @return array
     */
    public static function markUnpaid($tc_id)
    {
        $TransferCandidate = TransferCandidate::findOne($tc_id);

        if (!$TransferCandidate) {
            return [
                "operation" => "error",
                "message" => 'Candidate Transfer not found'
            ];
        }

        $TransferCandidate->paid = 0;

        if ($TransferCandidate->save(false)) {

            $Transfer = Transfer::findOne($TransferCandidate->transfer_id);
            // in case if transfer is paid
            if ($Transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE) {
                $Transfer->transfer_status = Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS;
                if ($Transfer->save(false)) {
                    return [
                        "operation" => "success",
                        "message" => 'Transfer marked as "unpaid" successfully'
                    ];
                } else {
                    return [
                        "operation" => "error",
                        "message" => $Transfer->errors
                    ];
                }
            }
        }
    }

    public static function markPaid($tc_id)
    {
        $TransferCandidate = TransferCandidate::findOne($tc_id);
        $TransferCandidate->paid = 1;
        if ($TransferCandidate->save()) {
            $unpaid = TransferCandidate::find()
                ->where([
                    'paid' => 0
                ])
                ->andWhere(['transfer_id' => $TransferCandidate->transfer_id])
                ->count();

            if (!$unpaid) {
                $transfer = Transfer::findOne($TransferCandidate->transfer_id);
                $transfer->transfer_status = Transfer::STATUS_TRANSFER_COMPLETE;
                if (!$transfer->save()) {
                    return [
                        "operation" => "error",
                        "message" => $TransferCandidate->errors
                    ];
                }
            }
            return [
                "operation" => "success",
                "message" => 'Transfer marked as "paid" successfully'
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $TransferCandidate->errors
            ];
        }
    }
}
