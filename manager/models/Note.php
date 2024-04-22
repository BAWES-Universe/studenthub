<?php
namespace manager\models;


/**
 * This is the model class for table "Note".
 * It extends from \common\models\Note but with custom functionality for this application module
 */
class Note extends \common\models\Note
{
    /**
     * Gets query for [[Invitation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvitation($modelName = '\manager\models\Invitation')
    {
        return parent::getInvitation($modelName);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelName = '\manager\models\Candidate')
    {
        return parent::getCandidate($modelName);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelName = '\manager\models\Request')
    {
        return parent::getRequest($modelName);
    }

    /**
     * Gets query for [[CompanyContact]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContact($modelName = '\manager\models\CompanyContact')
    {
        return parent::getCompanyContact($modelName);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelName = "\manager\models\Company")
    {
        return parent::getCompany($modelName);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\manager\models\Staff", $candidateClass = "\manager\models\Candidate")
    {
        return parent::getCreatedBy($modelClass, $candidateClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\manager\models\Staff", $candidateClass = "\manager\models\Candidate")
    {
        return parent::getUpdatedBy ($modelClass, $candidateClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\manager\models\Fulltimer")
    {
        return parent::getFulltimer ($modelClass);
    }
}
