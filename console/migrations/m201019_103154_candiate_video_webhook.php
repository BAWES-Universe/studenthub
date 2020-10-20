<?php

use yii\db\Migration;

/**
 * Class m201019_103154_candiate_video_webhook
 */
class m201019_103154_candiate_video_webhook extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        set_time_limit(0); // unlimited max execution time

        $this->addColumn('candidate', 'candidate_video_job_id', $this->string()->after('candidate_video')->null());

        //move videos from cloudinary to S3

        $path = (YII_ENV == 'prod') ? "candidate-video/" : "dev/candidate-video/";

        $candidates = $this->db->createCommand('select candidate_video from candidate WHERE candidate_video IS NOT NULL && candidate_video_processed = 1')->queryAll();

        foreach($candidates as $candidate) {

            $thumbnail = "https://res.cloudinary.com/studenthub/video/upload/q_auto/v1596453482/" . $path . $candidate['candidate_video'] . ".jpg";

            $video = "https://res.cloudinary.com/studenthub/video/upload/v1596453482/" . $path . $candidate['candidate_video'] . ".mp4";

            //copy to s3

            $key = 'candidate-video/' . $candidate['candidate_video'];

            $tmpVideo = sys_get_temp_dir() . '/' . $candidate['candidate_video'] . '.mp4';

            if (file_put_contents($tmpVideo, file_get_contents($video))) {

                Yii::$app->resourceManager->save(
                    null,
                    $key . '.mp4',
                    [],
                    $tmpVideo,
                    'video/mp4'
                );
            }

            @unlink($tmpVideo);

            $tmpImage = sys_get_temp_dir() . '/' . $candidate['candidate_video'] . '.jpg';

            if (file_put_contents($tmpImage, file_get_contents($thumbnail))) {

                Yii::$app->resourceManager->save(
                    null,
                    $key . '.jpg',
                    [],
                    $tmpImage,
                    'image/jpeg'
                );
            }

            @unlink($tmpImage);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201019_103154_candiate_video_webhook cannot be reverted.\n";

        return false;
    }
    */
}
