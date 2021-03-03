<?php
namespace candidate\models;


class Store extends \common\models\Store {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['store_total_candidates'],
            $fields['store_status'],
            $fields['store_created_at'],
            $fields['company_id'],
            $fields['store_updated_at']
        );
        // remove fields that contain sensitive information
        return $fields;
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \common\models\Store
     */
    public function getStoreManager($modelClass = "\candidate\models\Contact")
    {
        return parent::getStoreManager($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand($modelClass = "\candidate\models\Brand")
    {
        return parent::getBrand ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMall($modelClass = "\candidate\models\Mall")
    {
        return parent::getMall($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistory($modelClass = "\candidate\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistoryByLast40Days($modelClass = "\candidate\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistoryByLast40Days($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \staff\models\Store
     */
    public function getCandidatesCount($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidatesCount($modelClass);
    }
}
