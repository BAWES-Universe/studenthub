<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_education".
 *
 * @property string $education_uuid
 * @property int $candidate_id
 * @property int $university_id
 * @property string $degree_uuid
 * @property string $major_uuid
 * @property int $graduation_year
 * @property int $is_currently_studying
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Degree $degree
 * @property Major $major
 * @property University $university
 */
class CandidateEducation extends \yii\db\ActiveRecord
{
    // Education type constants
    const EDUCATION_TYPE_STANDARD = 'standard';
    const EDUCATION_TYPE_CUSTOM_UNIVERSITY = 'custom_university';
    const EDUCATION_TYPE_STUDYING_ABROAD = 'studying_abroad';
    const EDUCATION_TYPE_NOT_STUDYING = 'not_studying';

    /**
     * @return array List of education types
     */
    public static function getEducationTypes()
    {
        return [
            self::EDUCATION_TYPE_STANDARD => 'Standard',
            self::EDUCATION_TYPE_CUSTOM_UNIVERSITY => 'Custom University',
            self::EDUCATION_TYPE_STUDYING_ABROAD => 'Studying Abroad',
            self::EDUCATION_TYPE_NOT_STUDYING => 'Not Studying',
        ];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_education';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'education_type'], 'required'],
            [['candidate_id', 'university_id', 'graduation_year', 'is_currently_studying'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['education_uuid', 'degree_uuid', 'major_uuid'], 'string', 'max' => 60],
            [['custom_institution_name'], 'string', 'max' => 255],
            [['education_type'], 'in', 'range' => array_keys(self::getEducationTypes())],
            [['education_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['degree_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Degree::class, 'targetAttribute' => ['degree_uuid' => 'degree_uuid']],
            [['major_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Major::class, 'targetAttribute' => ['major_uuid' => 'major_uuid']],
            [['university_id'], 'exist', 'skipOnError' => true, 'targetClass' => University::class, 'targetAttribute' => ['university_id' => 'university_id']],
            
            // Custom validation for education type specific rules
            ['custom_institution_name', 'required', 'when' => function($model) {
                return in_array($model->education_type, [
                    self::EDUCATION_TYPE_CUSTOM_UNIVERSITY,
                    self::EDUCATION_TYPE_STUDYING_ABROAD
                ]);
            }, 'message' => 'Custom institution name is required'],
            
            // Set default value for education_type
            ['education_type', 'default', 'value' => self::EDUCATION_TYPE_STANDARD],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'education_uuid',
                ],
                'value' => function() {
                    if(!$this->education_uuid)
                        $this->education_uuid = 'education_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->education_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'education_uuid' => Yii::t('app', 'Education Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'university_id' => Yii::t('app', 'University ID'),
            'degree_uuid' => Yii::t('app', 'Degree Uuid'),
            'major_uuid' => Yii::t('app', 'Major Uuid'),
            'graduation_year' => Yii::t('app', 'Graduation Year'),
            'is_currently_studying' => Yii::t('app', 'Is Currently Studying'),
            'education_type' => Yii::t('app', 'Education Type'),
            'custom_institution_name' => Yii::t('app', 'Institution Name'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave ($insert, $changedAttributes);

        //update profile status

        //$this->candidate->isInCompleteProfile();

        //$this->candidate->candidate_pending_profile = implode(',', array_keys($this->candidate->pendingProfile));

        //!$this->candidate->university_id &&
        if($this->university_id) {
            $this->candidate->university_id = $this->university_id;
        }

        $this->candidate->setScenario('updatePendingProfile');
        $this->candidate->save(false);

        return true;
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            "candidate",
            "degree",
            "major",
            "university"
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = '\common\models\Candidate')
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDegree($modelClass = '\common\models\Degree')
    {
        return $this->hasOne($modelClass::className(), ['degree_uuid' => 'degree_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMajor($modelClass = '\common\models\Major')
    {
        return $this->hasOne($modelClass::className(), ['major_uuid' => 'major_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUniversity($modelClass = '\common\models\University')
    {
        return $this->hasOne($modelClass::className(), ['university_id' => 'university_id']);
    }
}
