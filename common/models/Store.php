<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;


/**
 * This is the model class for table "store".
 *
 * @property integer $store_id
 * @property integer $company_id
 * @property string $brand_uuid
 * @property string $store_name
 * @property string $store_total_candidates
 * @property integer $store_status
 * @property string $store_created_at
 * @property string $store_updated_at
 * @property integer $deleted
 *
 * @property Company $company
 * @property Candidate[] $candidates
 */
class Store extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['company_id', 'store_status', 'store_total_candidates'], 'integer'],
            [['store_name'], 'required'],
            [['store_created_at', 'store_updated_at','deleted','brand_uuid'], 'safe'],
            [['store_name'], 'string', 'max' => 255],
            [['company_id'], 'validateCompanyHasSubcompanies'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['brand_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Brand::className(), 'targetAttribute' => ['brand_uuid' => 'brand_uuid']],
        ];
    }

    /**
     * Find if company linked to store has subcompanies.
     * Parent Company that has subcompanies isn't allowed to have stores.
     */
    public function validateCompanyHasSubcompanies()
    {
        if($this->company && $this->company->subCompanies) {
            $this->addError('company_id', "Store can't be assigned to company having sub companies.");
        }
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'store_created_at',
                'updatedAtAttribute' => 'store_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_id' => Yii::t('app','Store ID'),
            'company_id' => Yii::t('app','Company ID'),
            'brand_uuid' => Yii::t('app','Brand UUID'),
            'store_name' => Yii::t('app','Store Name'),
            'store_status' => Yii::t('app','Store Status'),
            'store_created_at' => Yii::t('app','Store Created At'),
            'store_updated_at' => Yii::t('app','Store Updated At'),
            'deleted' => Yii::t('app','deleted'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted']);

        $fields['store_total_candidates'] = function($model) {
            return (int) $model->store_total_candidates;
        };
        
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'company',
            'candidates',
            'brand'
        ];
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])->andWhere(['deleted'=>0]);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])->andWhere(['deleted'=>0]);
    }

    /**
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;
        return $this->save(false);
    }
    
    /**
     * @inheritdoc
     * @return query\StoreQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\StoreQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand($modelClass = "\common\models\Brand")
    {
        return $this->hasOne($modelClass::className(), ['brand_uuid' => 'brand_uuid']);
    }
}
