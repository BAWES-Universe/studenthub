<?php
namespace admin\models;


/**
 * This is the model class for table "Store".
 * It extends from \common\models\Store but with custom functionality for this application module
 */
class Store extends \common\models\Store {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        // Whitelisted fields to return
        return [
            'store_id',
            'company_id',
            'store_name',
            'store_status'
        ];
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \common\models\Store
     */
    public function getStoreManager($modelClass = "\common\models\Contact")
    {
        return parent::getStoreManager($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand($modelClass = "\common\models\Brand")
    {
        return parent::getBrand ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMall($modelClass = "\common\models\Mall")
    {
        return parent::getMall($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistoryByLast40Days($modelClass = "\common\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistoryByLast40Days($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \staff\models\Store
     */
    public function getCandidatesCount($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidatesCount($modelClass);
    }
}
