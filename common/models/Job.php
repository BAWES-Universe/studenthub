<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "job".
 *
 * @property string $job_uuid
 * @property string $story_uuid
 * @property string $request_uuid
 * @property string $area_uuid
 * @property string $position
 * @property string $position_ar
 * @property string $description
 * @property string $description_ar
 * @property int $hours_per_day
 * @property int $days_per_week
 * @property string $compensation_type
 * @property double $compensation_amount
 * @property string $compensation_description
 * @property string $compensation_description_ar
 * @property int $min_age
 * @property int $max_age
 * @property int $gender MALE = 1, FEMALE = 2, OTHER = 3, Any = 4
 * @property string $available_from
 * @property string $available_to
 * @property int $status 0 -DRAFT | 1 - ACTIVE | 2- CLOSED
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 *
 * @property Area $area
 * @property Staff $createdBy
 * @property Request $request
 * @property Story $story
 * @property Staff $updatedBy
 * @property Staff $deletedBy
 * @property JobInterest[] $jobInterests
 * @property JobSkills[] $jobSkills
 */
class Job extends \yii\db\ActiveRecord
{
    const STATUS_DRAFT = 10;//default status
    const STATUS_ACTIVE = 1;
    const STATUS_CLOSED = 2;

    const TYPE_FIXED_PRICE = "FIXED_PRICE";
    const TYPE_HOURLY = "HOURLY";
    const TYPE_MONTHLY_SALARY = "MONTHLY_SALARY";

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_OTHER = 3;
    const GENDER_ANY = 4;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'job';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['story_uuid',  'position'], 'required'],//'request_uuid',
            [['description','description_ar', 'compensation_type', 'compensation_description', 'compensation_description_ar'],
                'string'],
            [['hours_per_day', 'days_per_week', 'min_age', 'max_age', 'gender', 'status'], 'integer'],
            [['compensation_amount'], 'number'],
            ['status', 'default', 'value' => self::STATUS_DRAFT],
            [['available_from', 'available_to', 'created_at', 'updated_at'], 'safe'],
            [['story_uuid', 'request_uuid', 'area_uuid'], 'string', 'max' => 60],//'job_uuid',
            [['position', 'position_ar'], 'string', 'max' => 255],
            //[['job_uuid'], 'unique'],
            [['story_uuid'], 'validateRequest'],
            [['area_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Area::class, 'targetAttribute' => ['area_uuid' => 'area_uuid']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['created_by' => 'staff_id']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::class, 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['story_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Story::class, 'targetAttribute' => ['story_uuid' => 'story_uuid']],
            [['deleted_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['deleted_by' => 'staff_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['updated_by' => 'staff_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()')
            ],
            [
                'class' => BlameableBehavior::class
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'job_uuid',
                ],
                'value' => function ($event) {
                    if (!$this->job_uuid)
                        $this->job_uuid = 'job_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    //= new Expression('UUID()');

                    return $this->job_uuid;
                },
            ]
        ];
    }

    /**
     * can't update for cancelled/completed request
     * @param type $attribute
     * @param type $params
     * @param type $validator
     */
    public function validateRequest($attribute, $params, $validator)
    {
        if (
            $this->story &&
            $this->story->request &&
            in_array(
                $this->story->request->request_status,
                [Request::STATUS_CANCELLED, Request::STATUS_DELIVERED]
            )
        ) {
            $this->addError($attribute, Yii::t('app', "Can't update for cancelled/completed request."));
        }
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$this->request_uuid) {
            $this->request_uuid = $this->story->request_uuid;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'job_uuid' => Yii::t('app', 'Job Uuid'),
            'story_uuid' => Yii::t('app', 'Story Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'area_uuid' => Yii::t('app', 'Area Uuid'),
            'position' => Yii::t('app', 'Position'),
            'description' => Yii::t('app', 'Description'),
            'position_ar' => Yii::t('app', 'Position - Arabic'),
            'description_ar' => Yii::t('app', 'Description - Arabic'),
            'hours_per_day' => Yii::t('app', 'Hours Per Day'),
            'days_per_week' => Yii::t('app', 'Days Per Week'),
            'compensation_type' => Yii::t('app', 'Compensation Type'),
            'compensation_amount' => Yii::t('app', 'Compensation Amount'),
            'compensation_description' => Yii::t('app', 'Compensation Description'),
            'compensation_description_ar' => Yii::t('app', 'Compensation Description - Arabic'),
            'min_age' => Yii::t('app', 'Min Age'),
            'max_age' => Yii::t('app', 'Max Age'),
            'gender' => Yii::t('app', 'Gender'),
            'available_from' => Yii::t('app', 'Available From'),
            'available_to' => Yii::t('app', 'Available To'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    /**
     * @return string[]
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['is_available'] = function ($data) {

            if ($data->available_from && strtotime(date("Y-m-d")) < strtotime($data->available_from)) {
                return false;
            }

            if ($data->available_to && strtotime(date("Y-m-d")) > strtotime($data->available_to)) {
                return false;
            }

            /*if ($data->status == self::STATUS_CLOSED) {
                return false;
            }*/

            return true;
        };

        return $fields;
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return array_merge([
            'area', 'jobSkills', 'jobInterests', 'createdBy',
            'updatedBy',
            "deletedBy",
            'request', 'story'
        ], parent::extraFields());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArea($modelClass = "\common\models\Area")
    {
        return $this->hasOne($modelClass::className(), ['area_uuid' => 'area_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStory($modelClass = "\common\models\Story")
    {
        return $this->hasOne($modelClass::className(), ['story_uuid' => 'story_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDeletedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'deleted_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJobInterests($modelClass = "\common\models\JobInterest")
    {
        return $this->hasMany($modelClass::className(), ['job_uuid' => 'job_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJobSkills($modelClass = "\common\models\JobSkills")
    {
        return $this->hasMany($modelClass::className(), ['job_uuid' => 'job_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\JobQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\JobQuery(get_called_class());
    }
}
