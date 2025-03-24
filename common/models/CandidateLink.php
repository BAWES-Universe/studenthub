<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_link".
 *
 * @property string $cl_uuid
 * @property int $candidate_id
 * @property string $title
 * @property string $url
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Candidate $candidate
 */
class CandidateLink extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_link';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //'cl_uuid',
            [['candidate_id', 'title', 'url'], 'required'],
            [['candidate_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['cl_uuid'], 'string', 'max' => 60],
            [['title', 'url'], 'string', 'max' => 255],
            [['cl_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cl_uuid',
                ],
                'value' => function() {
                    if(!$this->cl_uuid)
                        $this->cl_uuid = 'cl_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cl_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => "updated_at",
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
            'cl_uuid' => Yii::t('app', 'Cl Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'title' => Yii::t('app', 'Title'),
            'url' => Yii::t('app', 'Url'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Candidate]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasOne(Candidate::class, ['candidate_id' => 'candidate_id']);
    }
}
