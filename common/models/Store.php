<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;


/**
 * This is the model class for table "store".
 *
 * @property integer $store_id
 * @property integer $company_id
 * @property string $store_manager_uuid
 * @property string $brand_uuid
 * @property string $mall_uuid
 * @property string $store_name
 * @property string $store_location
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
            [['store_name', 'store_location'], 'required'],
            [['store_created_at', 'store_updated_at','deleted','brand_uuid'], 'safe'],
            [['store_name'], 'string', 'max' => 255],
            [['company_id'], 'validateCompanyHasSubcompanies'],
            [['store_manager_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyContact::class, 'targetAttribute' => ['store_manager_uuid' => 'contact_uuid']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['brand_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Brand::class, 'targetAttribute' => ['brand_uuid' => 'brand_uuid']],
            [['mall_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Mall::class, 'targetAttribute' => ['mall_uuid' => 'mall_uuid']],
        ];
    }

    /**
     * Scenarios for validation and massive assignment
     */
    public function scenarios() {
        $scenarios = parent::scenarios();

        $scenarios['create'] = ['company_id','store_name','store_location','brand_uuid','mall_uuid'];

        $scenarios['update'] = ['store_name','store_location','brand_uuid','mall_uuid'];

        $scenarios['update_manager'] = ['store_manager_uuid'];
        return $scenarios;
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
                'class' => TimestampBehavior::class,
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
            'store_manager_uuid' => Yii::t('app', 'Manager ID'),
            'brand_uuid' => Yii::t('app','Brand UUID'),
            'mall_uuid' => Yii::t('app','Mall UUID'),
            'store_name' => Yii::t('app','Store Name'),
            'store_location' => Yii::t('app', 'Store Location'),
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
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['store_id' => 'store_id']);
    }

    /**
     * Revenue
     * @return string
     */
    public function getRevenue()
    {
        return (double) $this->getTransferCandidates ()
            ->filterPaymentReceived()
            ->sum ('transfer_candidate.company_total');
    }

    /**
     * Revenue
     * @return string
     */
    public function getProfit()
    {
        return (double) $this->getTransferCandidates ()
            ->filterPaymentReceived()
            ->sum ('transfer_candidate.company_total - transfer_candidate.candidate_total');
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'storeManager',
            'company',
            'candidates',
            "candidatesSummary",
            'candidatesCount',
            'brand',
            'mall',
            'candidateWorkHistory',
            'candidateWorkHistoryByLast40Days',
            'profit',
            'revenue',
            "contracts"
        ];
    }

    /**
     * @param string $modelClass
     * @return $this
     *
    public function getStoreManager($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'store_manager_uuid']);
    }*/

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getStoreManager($modelClass = "\common\models\StoreManager")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
    }

    /**
     * @return Contract[]
     */
    public function getContracts($modelClass = "\common\models\Contract") {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);

        /*$contracts =  $this->company->contracts;

        if ($this->company->parentCompany)
            $contracts = array_merge($contracts, $this->company->parentCompany->contracts);

        return $contracts;*/
    }

    public function getActiveContracts($modelClass = "\common\models\Contract")
    {
        return $this->getContracts($modelClass)
            ->andWhere(['contract.deleted' => false])
            ->filterActive();
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])
            ->andWhere(['company.deleted'=>0]);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidatesSummary($modelClass = "\common\models\Candidate")
    {
        return $this->getCandidates($modelClass)
            ->limit(3);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
            ->andWhere(['candidate.deleted'=>0]);
        /*
            ->andWhere([
                "IN",
                "candidate_id",
                $this->getActiveContracts()
                    ->andWhere(new Expression("candidate_id IS NOT NULL"))
                    ->select('candidate_id')
            ])*/
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBrand($modelClass = "\common\models\Brand")
    {
        return $this->hasOne($modelClass::className(), ['brand_uuid' => 'brand_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMall($modelClass = "\common\models\Mall")
    {
        return $this->hasOne($modelClass::className(), ['mall_uuid' => 'mall_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasMany($modelClass::className(), ['store_id' => 'store_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistoryByLast40Days($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->getCandidateWorkHistory()
            ->andWhere('DATE(start_date) > DATE_SUB(NOW(),INTERVAL 40 DAY)')
            ->orderBy('start_date ASC')
            ->exists();
    }

    /**
     * @param string $modelClass
     * @return \staff\models\Store
     */
    public function getCandidatesCount($modelClass = "\staff\models\Candidate")
    {
        return $this->getCandidates($modelClass)
            ->count();
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
