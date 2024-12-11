<?php
namespace staff\models;

use common\models\StaffToken;
use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "Staff".
 * It extends from \common\models\Staff but with custom functionality for this application module
 */
class Staff extends \common\models\Staff {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['staff_auth_key'],
        $fields['staff_password_hash'],
        $fields['staff_password_reset_token']);

        $fields['total_assigned'] = function ($model) {
            return $model->getCandidateWorkHistories()->count();
        };

        $fields['total_requests'] = function ($model) {
            return $model->getRequests()->count();
        };

        $fields['total_notes'] = function ($model) {
            return $model->getNotes()->count();
        };

        return $fields;
    }

    /**
     * @return array
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(),[
            'staffSalaries',
            'storyActivities',
            'groupStoryActivities',
            'activeStory',
            'oldStories'
        ]);
    }
    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getStoryActivities($modelClass = "\staff\models\StoryActivity")
    {
        return parent::getStoryActivities($modelClass);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getGroupStoryActivities($modelClass = "\staff\models\StoryActivity")
    {
        return parent::getStoryActivities($modelClass)->groupBy('story_uuid')->all();
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getActiveStoriesActivities($modelClass = "\staff\models\StoryActivity")
    {
        return $this->getStoryActivities()
            ->andWhere(new Expression('activity_time_spent IS NOT null'))
            ->andWhere(['activity_status'=> 1])
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStories($modelClass = "\staff\models\Story")
    {
        return parent::getStories($modelClass);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveStory()
    {
        return $this->getStories()->andWhere(['story_status'=>Story::STATUS_STARTED])->all();
    }

    public function getOldStories()
    {
        return $this->getStories()
            ->andWhere(['!=', 'story_status', Story::STATUS_STARTED])
            ->orderBy('story_last_updated_at DESC');
    }

    /**
     * @param mixed $token
     * @param null $type
     * @return mixed
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = StaffToken::find()
            ->andWhere(['token_value' => $token])
            ->with('staff')
            ->one();

        if($token) {
            return $token->staff;
        }
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\StaffToken")
    {
        return parent::getAccessTokens($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\staff\models\Note")
    {
        return parent::getNotes($modelClass);
    }
}
