<?php
namespace staff\models;

use Yii;

/**
 * This is the model class for table "Note".
 * It extends from \common\models\Note but with custom functionality for this application module
 */
class Note extends \common\models\Note {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return parent::fields();
    }

    /**
     * Gets query for [[Invitation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvitation($modelName = '\staff\models\Invitation')
    {
        return parent::getInvitation($modelName);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }
}
