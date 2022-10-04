<?php
namespace status\models;

use common\models\StaffToken;
use Yii;

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
            $fields['staff_password_reset_token'],
            $fields['staff_gmail_username'],
            $fields['staff_gmail_password']
        );

        /*$fields['total_assigned'] = function ($model) {
            return $model->getCandidateWorkHistories()->count();
        };
        $fields['total_requests'] = function ($model) {
            return $model->getRequests()->count();
        };

        $fields['total_notes'] = function ($model) {
            return $model->getNotes()->count();
        };*/

        return $fields;
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
            ->andWhere(['<>','activity_time_spent','null'])
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

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\StaffToken")
    {
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\staff\models\Note")
    {
        return parent::getNotes($modelClass);
    }
}
