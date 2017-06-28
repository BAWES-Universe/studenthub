<?php
namespace company\models;

use Yii;

/**
 * This is the model class for table "Invoice".
 * It extends from \common\models\Invoice but with custom functionality for this application module
 */
class Invoice extends \common\models\Invoice {
    
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        return $fields;
    }
}
