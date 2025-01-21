<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_id_request".
 *
 * @property string $cir_uuid
 * @property string|null $candidate_ids
 * @property string|null $status
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property Staff $createdBy
 * @property Staff $updatedBy
 */
class CandidateIdRequest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_id_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           // [['cir_uuid'], 'required'],
            [['candidate_ids'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['created_by', 'updated_by'], 'integer'],
            [['cir_uuid'], 'string', 'max' => 60],
            [['status'], 'string', 'max' => 100],
            [['cir_uuid'], 'unique'],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['created_by' => 'staff_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['updated_by' => 'staff_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cir_uuid' => Yii::t('app', 'Cir Uuid'),
            'candidate_ids' => Yii::t('app', 'Candidate Ids'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cir_uuid',
                ],
                'value' => function() {
                    if(!$this->cir_uuid)
                        $this->cir_uuid = 'cir_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cir_uuid;
                }
            ],
        ];
    }
    
    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(Staff::class, ['staff_id' => 'created_by']);
    }

    /**
     * Gets query for [[UpdatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(Staff::class, ['staff_id' => 'updated_by']);
    }
}
