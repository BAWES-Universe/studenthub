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
 * @property string $store_name
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
            [['company_id', 'store_status'], 'integer'],
            [['store_name'], 'required'],
            [['store_created_at', 'store_updated_at','deleted'], 'safe'],
            [['store_name'], 'string', 'max' => 255],
            [['company_id'], 'validateCompany'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
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
     * find if company have subcompanies
     */
    public function validateCompany()
    {
        if($this->company && $this->company->subCompanies) {
            $this->addError('company_id', "Store can't be assigned to company having sub companies.");   
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_id' => 'Store ID',
            'company_id' => 'Company ID',
            'store_name' => 'Store Name',
            'store_status' => 'Store Status',
            'store_created_at' => 'Store Created At',
            'store_updated_at' => 'Store Updated At',
            'deleted' => 'deleted',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates()
    {
        return $this->hasMany(Candidate::className(), ['store_id' => 'store_id']);
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
}
