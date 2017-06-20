<?php
namespace company\models;

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
        unset($fields['company_id'],$fields['total'],$fields['parent_transfer_id']);

        // Update Datetime output
        $fields['transfer_created_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->transfer_created_at);
        };
        $fields['transfer_updated_at'] = function($model) {
            return Yii::$app->formatter->asDate($model->transfer_updated_at);
        };

        return $fields;
    }

}
