<?php
namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "story".
 *
 * @property string $story_uuid
 * @property string $request_uuid
 * @property string $suggestion_uuid
 * @property int $staff_id
 * @property int $story_status
 * @property int $is_old
 * @property int $story_time_spent
 * @property string $story_created_at
 * @property string $story_last_updated_at
 *
 * @property Request $requestUu
 * @property StoryActivity[] $storyActivities
 */
class Story extends \yii\db\ActiveRecord
{
    const STATUS_UNSTARTED = 0;
    const STATUS_STARTED = 1;
    const STATUS_FINISHED = 2;
    const STATUS_DELIVERED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_ACCEPTED = 5;
    const STATUS_CANCELLED = 6;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'story';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['story_uuid','request_uuid'], 'required'],
            ['story_status', 'in', 'range' => [self::STATUS_UNSTARTED, self::STATUS_STARTED, self::STATUS_FINISHED,self::STATUS_DELIVERED,self::STATUS_REJECTED,self::STATUS_ACCEPTED]],
            [['story_status', 'story_time_spent','staff_id','is_old'], 'integer'],
            [['story_created_at', 'story_last_updated_at'], 'safe'],
            [['story_uuid', 'request_uuid','suggestion_uuid'], 'string', 'max' => 60],
            [['story_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['suggestion_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Suggestion::className(), 'targetAttribute' => ['suggestion_uuid' => 'suggestion_uuid']],
        ];
    }


    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'story_uuid',
                ],
                'value' => function() {
                    if (!$this->story_uuid)
                        $this->story_uuid = 'story_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->story_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'story_created_at',
                'updatedAtAttribute' => 'story_last_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @return bool|void
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if($insert) {
            $storyActivity = new StoryActivity();
            $storyActivity->story_uuid = $this->story_uuid;
            $storyActivity->activity_status = Story::STATUS_UNSTARTED;
            $storyActivity->save(false);

            return true;
        }

        $request = Request::findOne($this->request_uuid);

        if(
            isset($changedAttributes['story_status']) &&
            $this->story_status == self::STATUS_STARTED &&
            $request->request_status == Request::STATUS_PENDING
        ) {
            $request->request_status = Request::STATUS_STARTED;
        }

        //Update request time spent

        if(isset($changedAttributes['story_time_spent']))
        {
            $request->request_time_spent = $request->getStories()->sum('story_time_spent');
            $request->save(false);
        }
        else
        {
            //update `request_updated_at` field
            $request->request_updated_datetime = '';
            $request->update(false);
        }
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'request',
            'company',
            'staff',
            'storyActivities',
            'latestStoryActivity'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'story_uuid' => 'Story Uuid',
            'request_uuid' => 'Request Uuid',
            'staff_id' => 'Staff id',
            'story_status' => 'Story Status',
            'is_old' => 'Is Old',
            'story_time_spent' => 'Story Time Spent',
            'story_created_at' => 'Story Created At',
            'story_last_updated_at' => 'Story Last Updated At',
        ];
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
    public function getSuggestion($modelClass = "\common\models\Suggestion")
    {
        return $this->hasOne($modelClass::className(), ['suggestion_uuid' => 'suggestion_uuid']);
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
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id'])
            ->via('request');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveStoryActivity($modelClass = "\common\models\StoryActivity")
    {
        return $this->hasOne($modelClass::className(), ['story_uuid' => 'story_uuid'])
            ->andWhere(['activity_status' => StoryActivity::STATUS_STARTED]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStoryActivities($modelClass = "\common\models\StoryActivity")
    {
        return $this->hasMany($modelClass::className(), ['story_uuid' => 'story_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLatestStoryActivity($modelClass = "\common\models\StoryActivity")
    {
        return $this->getStoryActivities()->orderBy('activity_created_at DESC')->one();
    }
}
