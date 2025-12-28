<?php

use yii\db\Migration;
use common\models\Story;
use staff\models\Request;
/**
 * Class m211215_105556_request_to_stories
 */
class m211215_105556_request_to_stories extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('story')
            ->getColumn('is_old');

        if (!$columnData) {
            $this->addColumn('story', 'is_old', $this->tinyInteger(1)->defaultValue(0)->after('story_status'));
        }

        $no_of_employees_per_story = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('request')
            ->getColumn('no_of_employees_per_story');

        if (!$no_of_employees_per_story) {
            $this->addColumn ('request', 'no_of_employees_per_story', $this->smallInteger (6)->defaultValue(0)->after('request_number_of_employees'));
        }

        $sql = "select * from request where 1";

        $requests = Yii::$app->db->createCommand ($sql)->queryAll ();

        foreach ($requests as $request) {

                $request['request_number_of_employees'];
                // Fixed for MySQL 9 ONLY_FULL_GROUP_BY compatibility: select only updated_by which is what we group by
                $noteQuery = "SELECT updated_by FROM `note` where (note_type='Suggested' OR note_type='Internal Note') and request_uuid='".$request['request_uuid']."' group by updated_by";

                $notes = Yii::$app->db->createCommand ($noteQuery)->queryAll ();
                $story_uuid = 'story_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                $request_uuid = $request['request_uuid'];
                $suggestion_uuid = NULL;
                $story_status = 0;

                if ($request['request_status'] == Request::STATUS_STARTED || $request['request_status'] == Request::STATUS_RE_WORK) {
                    $story_status = Story::STATUS_STARTED;
                } else if ($request['request_status'] == Request::STATUS_DELIVERED || $request['request_status'] == Request::STATUS_FINISHED) {
                    $story_status = Story::STATUS_DELIVERED;
                } else if ($request['request_status']  == Request::STATUS_CANCELLED) {
                    $story_status = Story::STATUS_REJECTED;
                }

                if ($notes) {
                    $staff_ids = array_keys(\yii\helpers\ArrayHelper::map($notes, 'updated_by', 'updated_by'));
                    $staff_id = $staff_ids[array_rand($staff_ids)];
                }

                if($staff_id && $staff_id == 1)
                {
                    $staff_id = 2;
                }
                else if(!$staff_id) 
                {
                    $staff_id = 'NULL';
                }

                $storyInsert = "INSERT INTO `story` (`story_uuid`, `request_uuid`, `suggestion_uuid`, `staff_id`, `story_status`, `is_old`, `story_time_spent`, `story_created_at`, `story_last_updated_at`) VALUES
                ('$story_uuid', '$request_uuid', NULL, $staff_id,'$story_status', 1, 1, NOW(), NOW())";
                Yii::$app->db->createCommand ($storyInsert)->execute();

                $story_activity_uuid = 'stry_act_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                $storyDetailInsert = "INSERT INTO `story_activity` (`story_activity_uuid`, `story_uuid`, `staff_id`, `activity_time_spent`, `activity_status`, `activity_created_at`, `activity_last_updated_at`) VALUES
                ('$story_activity_uuid', '$story_uuid', $staff_id, 1, $story_status, NOW(), NOW())";
                Yii::$app->db->createCommand ($storyDetailInsert)->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('story', 'is_old');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211215_105556_request_to_stories cannot be reverted.\n";

        return false;
    }
    */
}
