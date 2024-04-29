<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "request_application".
 *
 * @property string $application_uuid
 * @property string $request_uuid
 * @property string $fulltimer_uuid
 * @property int $candidate_id
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Fulltimer $fulltimer
 * @property Request $request
 */
class RequestApplication extends \yii\db\ActiveRecord
{
    const STATUS_APPLIED = 0;
    const STATUS_INTERVIEW_SCHEDULED = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_application';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_uuid'], 'required'],//'application_uuid',
            [['candidate_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['application_uuid', 'request_uuid', 'fulltimer_uuid'], 'string', 'max' => 60],
            [['request_uuid'], "validateUniqueApplication"],
            [['application_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
        ];
    }

    /**
     * Validate unique application
     */
    public function validateUniqueApplication($attribute, $params, $validator) {

        if(empty($this->fulltimer_uuid) && empty($this->candidate_id)) {
            $this->addError('candidate_id', Yii::t('app', "Candidate details missing"));
        }

        $query = self::find()
            ->andWhere(['request_uuid' => $this->request_uuid]);

        if($this->application_uuid) {
            $query->andWhere(['!=', 'application_uuid', $this->application_uuid]);
        }

        if($this->fulltimer_uuid) {
            $query->andWhere(['fulltimer_uuid' => $this->fulltimer_uuid]);
        }

        if($this->candidate_id) {
            $query->andWhere(['candidate_id' => $this->candidate_id]);
        }

        if($query->exists()) {
            $this->addError('request_uuid', Yii::t('app', "Already applied!"));
        }
    }

    /**
     * @return array[]
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'application_uuid',
                ],
                'value' => function () {
                    if (!$this->application_uuid)
                        $this->application_uuid = 'application_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->application_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
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
            'application_uuid' => Yii::t('app', 'Application Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }
}
