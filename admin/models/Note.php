<?php
namespace admin\models;

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
        $fields = parent::fields();

        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\admin\models\Staff", $candidateClass = "\admin\models\Candidate")
    {
        return parent::getcreatedBy($modelClass, $candidateClass);
    }

    /**
     * Gets query for [[Invitation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvitation($modelName = '\common\models\Invitation')
    {
        return parent::getInvitation ($modelName);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelName = '\admin\models\Candidate')
    {
        return parent::getCandidate($modelName);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelName = '\common\models\Request')
    {
        return parent::getRequest($modelName);
    }

    /**
     * Gets query for [[CompanyContact]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContact($modelName = '\common\models\CompanyContact')
    {
        return parent::getCompanyContact($modelName);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\admin\models\Staff", $candidateClass = "\admin\models\Candidate")
    {
        return parent::getUpdatedBy($modelClass, $candidateClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }
}
