<?php

namespace candidate\models;


class Note extends \common\models\Note
{
    /**
     * Gets query for [[Invitation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvitation($modelName = '\candidate\models\Invitation')
    {
        return parent::getInvitation($modelName);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelName = '\candidate\models\Candidate')
    {
        return parent::getCandidate($modelName);
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelName = '\candidate\models\Request')
    {
        return parent::getRequest($modelName);
    }

    /**
     * Gets query for [[CompanyContact]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompanyContact($modelName = '\candidate\models\CompanyContact')
    {
        return parent::getCompanyContact($modelName);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\candidate\models\Staff", $candidateClass = "\candidate\models\Candidate")
    {
        return parent::getCreatedBy($modelClass, $candidateClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\candidate\models\Staff", $candidateClass = "\candidate\models\Candidate")
    {
        return parent::getUpdatedBy($modelClass, $candidateClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\candidate\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }
}