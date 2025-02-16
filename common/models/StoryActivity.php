<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "story_activity".
 *
 * @property string $story_activity_uuid
 * @property string $story_uuid
 * @property int $staff_id
 * @property int $activity_time_spent
 * @property int $activity_status
 * @property string $activity_created_at
 * @property string $activity_last_updated_at
 *
 * @property Staff $staff
 * @property Story $story
 */
class StoryActivity extends \yii\db\ActiveRecord
{
    const STATUS_UNSTARTED = 0;
    const STATUS_STARTED = 1;
    const STATUS_FINISHED = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_ACCEPTED = 5;
    const STATUS_CANCELLED = 6;
    const STATUS_REWORK = 7;
    const STATUS_STOPPED = 8;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'story_activity';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['story_uuid'], 'required'],
            ['activity_status', 'in', 'range' => [self::STATUS_UNSTARTED, self::STATUS_STARTED, self::STATUS_FINISHED,self::STATUS_DELIVERED,self::STATUS_REJECTED,self::STATUS_ACCEPTED,self::STATUS_STOPPED,self::STATUS_REWORK]],
            [['staff_id', 'activity_time_spent', 'activity_status'], 'integer'],
            [['activity_last_updated_at','activity_created_at'], 'safe'],
            [['story_activity_uuid', 'story_uuid'], 'string', 'max' => 60],
            [['story_activity_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['story_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Story::class, 'targetAttribute' => ['story_uuid' => 'story_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'story_activity_uuid' => 'Story Activity Uuid',
            'story_uuid' => 'Story Uuid',
            'staff_id' => 'Staff ID',
            'activity_time_spent' => 'Activity Time Spent',
            'activity_status' => 'Activity Status',
            'activity_created_at' => 'Activity Created At',
            'activity_last_updated_at' => 'Activity Datetime',
        ];
    }

    /**
     * @param $insert
     * @return bool
     * @throws \Exception
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        //if($this->activity_status == self::STATUS_UNSTARTED) {
        if(in_array($this->activity_status, [self::STATUS_UNSTARTED, self::STATUS_STARTED, self::STATUS_REWORK])) {
            $this->activity_time_spent = 0;
            return true;
        }

        $lastActivity = $this->story->getStoryActivities()
            ->orderBy('activity_created_at desc')
            ->one();

        if($lastActivity)
        {
            $activity_created_at = $lastActivity->activity_created_at?
                new \DateTime(date ('Y-m-d H:i:s', strtotime ($lastActivity->activity_created_at))): new \DateTime();

            $activity_last_updated_at = new \DateTime(date ('Y-m-d H:i:s'));

            $diff = $activity_created_at->diff ($activity_last_updated_at);
            $daysInSecs = $diff->format ('%r%a') * 24 * 60 * 60;
            $hoursInSecs = $diff->h * 60 * 60;
            $minsInSecs = $diff->i * 60;

            $seconds = $daysInSecs + $hoursInSecs + $minsInSecs + $diff->s;

            $this->activity_time_spent = $seconds;
        }

        return true;
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $story = Story::find()
            ->andWhere(["story_uuid" => $this->story_uuid])
            ->one();

        if (!$story) {
            throw new NotFoundHttpException('The requested record does not exist.');
        }

        if($this->staff_id) {
            $story->staff_id = $this->staff_id;
        }

        $story->story_status = $this->activity_status;

        $story->story_time_spent = $story->getStoryActivities()
            ->sum('activity_time_spent');

        if($this->staff_id)
            $story->staff_id = $this->staff_id;
        else
            $story->staff_id = null;

        $story->save(false);
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'story_activity_uuid',
                ],
                'value' => function() {
                    if (!$this->story_activity_uuid)
                        $this->story_activity_uuid = 'stry_act_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->story_activity_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'activity_created_at',
                'updatedAtAttribute' => 'activity_last_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function extraFields()
    {
        return [
            'story',
            'staff'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
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
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid'])->via('story');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])->via('request');
    }
}
