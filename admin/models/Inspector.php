<?php
namespace admin\models;

use Yii;


/**
 * This is the model class for table "Inspector".
 * It extends from \common\models\Inspector but with custom functionality for this application module
 */
class Inspector extends \common\models\Inspector {

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = '\common\models\InspectorToken')
    {
        return parent::getAccessTokens($modelClass);
    }
}
