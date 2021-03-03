<?php
namespace company\models;


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

        $fields = parent::fields();

        unset($fields['deleted']);

        return $fields;
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            'totalCandidates'
        ]);
    }
    
    public function getTotalCandidates() 
    {
        return count($this->candidates);
    }
        
    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \admin\models\Store
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \common\models\Store
     */
    public function getStoreManager($modelClass = "\company\models\Contact")
    {
        return parent::getStoreManager($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand($modelClass = "\company\models\Brand")
    {
        return parent::getBrand ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMall($modelClass = "\company\models\Mall")
    {
        return parent::getMall($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistory($modelClass = "\company\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistoryByLast40Days($modelClass = "\company\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistoryByLast40Days($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \staff\models\Store
     */
    public function getCandidatesCount($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidatesCount($modelClass);
    }
}
