<?php
namespace staff\models;

use Yii;

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
        return parent::fields();
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return array_merge(
            parent::extraFields(),[
                'candidates',
                'storeWithCompany' => function($model) {
                    return $model->store_name." @ ".$model->company->company_name;
                }
            ]
        );
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}
