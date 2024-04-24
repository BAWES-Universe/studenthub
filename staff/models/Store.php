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
            parent::extraFields(),
            [
                'candidates',
                'storeWithCompany' => function($model) {
                    if (isset($model->store_name) && isset($model->company->company_name)) {
                        $name = ($model->company->company_common_name_en) ? $model->company->company_common_name_en : $model->company->company_name;
                        return $model->store_name." @ ". $name;
                    }
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMall($modelClass = "\staff\models\Mall")
    {
        return parent::getMall($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \common\models\Store
     */
    public function getStoreManager($modelClass = "\common\models\StoreManager")
    {
        return parent::getStoreManager($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand($modelClass = "\staff\models\Brand")
    {
        return parent::getBrand ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistory($modelClass = "\staff\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistoryByLast40Days($modelClass = "\staff\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistoryByLast40Days($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \staff\models\Store
     */
    public function getCandidatesCount($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidatesCount($modelClass);
    }
}
