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
}
