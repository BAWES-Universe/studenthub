<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "candidate_video_log".
 *
 * @property string $video_log_uuid
 * @property int $candidate_id
 * @property string $ip_address
 * @property string $created_at
 *
 * @property Candidate $candidate
 */
class CandidateVideoLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_video_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip_address', 'candidate_id'], 'required'],
            [['candidate_id'], 'integer'],
            [['created_at'], 'safe'],
            [['video_log_uuid'], 'string', 'max' => 60],
            [['ip_address'], 'string', 'max' => 45],
            [['video_log_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'video_log_uuid',
                ],
                'value' => function() {
                    if (!$this->video_log_uuid)
                        $this->video_log_uuid = 'video_log_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->video_log_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'video_log_uuid' => Yii::t('app', 'Video Log Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($className = '\common\models\Candidate')
    {
        return $this->hasOne($className::className(), ['candidate_id' => 'candidate_id']);
    }
}
