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

        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('candidate')
            ->getColumn('candidate_video_job_id');

        if (!$columnData) {
            $this->addColumn('candidate', 'candidate_video_job_id', $this->string()->after('candidate_video')->null());
        }

        //move videos from cloudinary to S3

        $path = (YII_ENV == 'prod') ? "candidate-video/" : "dev/candidate-video/";

        $candidates = $this->db->createCommand('select candidate_id, candidate_video from candidate WHERE candidate_video IS NOT NULL && candidate_video_processed = 1')->queryAll();

        foreach($candidates as $candidate) {

            $candidateVideo = explode('.', $candidate['candidate_video'])[0];

            $thumbnail = "https://res.cloudinary.com/studenthub/video/upload/q_auto/v1596453482/" . $path . $candidateVideo . ".jpg";

            $video = "https://res.cloudinary.com/studenthub/video/upload/v1596453482/" . $path . $candidateVideo . ".mp4";

            //copy to s3

            $videoKey = 'candidate-video/' . $candidateVideo . '.mp4';

            $tmpVideo = sys_get_temp_dir() . '/' . $candidateVideo . '.mp4';

            if (file_put_contents($tmpVideo, file_get_contents($video))) {

                Yii::$app->resourceManager->save(
                    null,
                    $videoKey,
                    [],
                    $tmpVideo,
                    'video/mp4'
                );
            }

            @unlink($tmpVideo);

            $imageKey = 'candidate-video/' . $candidateVideo  . '.jpg';

            $tmpImage = sys_get_temp_dir() . '/' . $candidateVideo . '.jpg';

            if (file_put_contents($tmpImage, file_get_contents($thumbnail))) {

                Yii::$app->resourceManager->save(
                    null,
                    $imageKey,
                    [],
                    $tmpImage,
                    'image/jpeg'
                );
            }

            @unlink($tmpImage);

            $this->db->createCommand('UPDATE candidate SET candidate_video="'.$candidateVideo.'" WHERE candidate_id = "'.$candidate['candidate_id'].'"')->execute();

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
