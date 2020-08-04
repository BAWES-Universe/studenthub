<?php

namespace console\controllers;

use Yii;
use yii\helpers\Console;
use common\models\Candidate;


/**
 * All Resource actions related to this project
 */
class ResourceController extends \yii\console\Controller {

    /**
     * move s3 profile photos to cloudinary
     */
    public function actionS3ToCloudinary() {
        
        $query = Candidate::find()->where(['like', 'candidate_personal_photo', 'photos/']);

        $total = $query->count();
       
        Console::startProgress(0, $total);

        $n = 0;
         
        foreach ($query->batch(100) as $candidates) {
 
            foreach ($candidates as $candidate) {

                $candidate_personal_photo = $candidate->candidate_personal_photo;
                    
                //s3 bucket url 

                $url = Yii::$app->resourceManager->getUrl($candidate_personal_photo);

                if ($candidate->setProfileByUrl($url)) {
                    $candidate->save(false);
                }

                //remove S3 image from bucket

                Yii::$app->resourceManager->delete($candidate_personal_photo);

                $n++;
                
                Console::updateProgress($n, $total);
            }
        }
    }

}
