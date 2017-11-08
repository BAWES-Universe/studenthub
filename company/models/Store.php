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
        return [
            'store_id',
            'company_id',
            'store_name',
            'store_status',
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            'candidates',
            'totalCandidates'
        ];
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
}
