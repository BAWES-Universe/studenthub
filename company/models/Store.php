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
            'candidates' => function($model) {
                return $model->candidates;
            }
        ];
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
