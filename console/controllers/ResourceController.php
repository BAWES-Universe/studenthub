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

    /**
     * add missing transfer file entries
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function populateTransferFileEntries() {

        $transfer_files = \common\models\TransferFile::find ()
            ->andWhere('transfer_file_id NOT IN (select DISTINCT(transfer_file_id) from transfer_file_entry)');

        foreach ($transfer_files->each (1) as $transfer_file) {

            $transaction = Yii::$app->db->beginTransaction ();

            if(!$transfer_file->populateEntries()) {
                $transaction->rollBack ();
                throw new \yii\web\BadRequestHttpException('Error populating entries for transfer file #' . $transfer_file->transfer_file_id);
            }

            $transaction->commit ();
        }

    }
}
