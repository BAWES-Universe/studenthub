<?php

namespace console\controllers;

use admin\models\Staff;
use agent\models\Agent;
use common\models\Provider;
use Yii;
use yii\helpers\Console;
use common\models\Candidate;

/**
 * All Resource actions related to this project
 */
class CentralDbController extends \yii\console\Controller {

    /**
     * move s3 profile photos to cloudinary
     */
    public function actionIndex() {
        
        $query = Candidate::find()->andWhere('candidate_id > 26234');

        $total = $query->count();
       
        Console::startProgress(0, $total);

        $n = 0;
         $email = ['anilkumar.dhiman1@gmail.com','ravi.tilavat@bawes.net','alsayedfishing@gmail.com','jarrah.dosary@gmail.com','afifomar007@gmail.com','sarah.alamer28@gmail.com','mariam.aladwani@outlook.com','yousefgamer777@gmail.com','musthafamohideen@yahoo.com','musthafamohideen@yahoo.co.in','abdullahrazouqi@icloud.com','lolo.ja09@gmail.com'];
        foreach ($query->batch(1) as $candidates) {
            $users = [];
            foreach ($candidates as $candidate) {

                if (in_array($candidate->candidate_email,$email)) {
                    continue;
                }
                $users[] = [
                    $candidate->candidate_id,
                    $candidate->candidate_name,
                    $candidate->candidate_name,
                    $candidate->candidate_email,
                    str_replace("$2y$13","$2b$13",$candidate->candidate_password_hash),
                    $candidate->candidate_email_verification,
                    \GuzzleHttp\json_encode(['app' => 'SH-candidate', 'user_id' => $candidate->candidate_id]),
                    $candidate->candidate_created_at,
                    $candidate->candidate_updated_at
                ];
                $n++;
                
                Console::updateProgress($n, $total);
            }
            Yii::$app->db2->createCommand()->batchInsert('{{%users}}', [
                'user_id', 'name', 'nickname', 'email', 'password','email_verified','user_metadata','created_at','updated_at'
            ], $users)->execute ();
        }
    }

    public function actionStaff() {

        $query = Staff::find()->andWhere('deleted = 0');

        $total = $query->count();

        Console::startProgress(0, $total);

        $n = 0;
        foreach ($query->batch(25) as $candidates) {
            $users = [];
            foreach ($candidates as $candidate) {

                $users[] = [
                    $candidate->staff_id,
                    $candidate->staff_name,
                    $candidate->staff_name,
                    $candidate->staff_email,
                    str_replace("$2y$13","$2b$13",$candidate->staff_password_hash),
                    \GuzzleHttp\json_encode(['app' => 'SH-staff', 'user_id' => $candidate->staff_id]),
                    $candidate->staff_created_at,
                    $candidate->staff_updated_at
                ];
                $n++;

                Console::updateProgress($n, $total);
            }
            Yii::$app->db2->createCommand()->batchInsert('{{%users}}', [
                'user_id', 'name', 'nickname', 'email', 'password','user_metadata','created_at','updated_at'
            ], $users)->execute ();
        }
    }

    /**
     * plugn
     */
    public function actionAgent() {

        $query = Agent::find();

        $total = $query->count();

        Console::startProgress(0, $total);

        $n = 0;
        foreach ($query->batch(50) as $agents) {
            $users = [];
            foreach ($agents as $agent) {

                $users[] = [
                    $agent->agent_id,
                    $agent->agent_name,
                    $agent->agent_name,
                    $agent->agent_email,
                    str_replace("$2y$13","$2b$13",$agent->agent_password_hash),
                    $agent->agent_email_verification,
                    \GuzzleHttp\json_encode(['app' => 'SH-plugin', 'user_id' => $agent->agent_id]),
                    $agent->agent_created_at,
                    $agent->agent_updated_at
                ];
                $n++;

                Console::updateProgress($n, $total);
            }
            Yii::$app->db2->createCommand()->batchInsert('{{%users}}', [
                'user_id', 'name', 'nickname', 'email', 'password','email_verified','user_metadata','created_at','updated_at'
            ], $users)->execute ();
        }
    }

    /**
     * tamr
     */
    public function actionProvider() {

        $query = Provider::find();

        $total = $query->count();

        Console::startProgress(0, $total);

        $n = 0;
        foreach ($query->batch(50) as $providers) {
            $users = [];
            foreach ($providers as $provider) {

                $users[] = [
                    $provider->provider_uuid,
                    $provider->provider_name,
                    $provider->provider_name,
                    $provider->provider_email,
                    str_replace("$2y$13","$2b$13",$provider->provider_password_hash),
                    \GuzzleHttp\json_encode(['app' => 'SH-tamr-provider', 'user_id' => $provider->provider_uuid]),
                    $provider->provider_created_at,
                    $provider->provider_updated_at
                ];
                $n++;

                Console::updateProgress($n, $total);
            }
            Yii::$app->db2->createCommand()->batchInsert('{{%users}}', [
                'user_id', 'name', 'nickname', 'email', 'password','user_metadata','created_at','updated_at'
            ], $users)->execute ();
        }
    }
}
