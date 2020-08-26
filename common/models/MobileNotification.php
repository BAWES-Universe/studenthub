<?php
namespace common\models;

use Yii; 


/**
 * Send oneSignal notifications
 * @author krushn
 */
class MobileNotification {
    
    /**
     * 
     * @param string $headings
     * @param string $subtitle
     * @param string $content
     * @param array[
     *   [
     *       "field" => "tag",
     *       "key" => "email",
     *       "relation" => "=",
     *       "value" => $agent['email']
     *   ]
     * ] $filters;
     */
    public static function notifyCandidate($heading, $data, $filters, $subtitle = '', $content = '')
    {
        if(!isset(Yii::$app->params['oneSignalCandidateAPPID'])) {
            return false; 
        }
        
        self::sendNotification(
            Yii::$app->params['oneSignalCandidateAPPID'], 
            Yii::$app->params['oneSignalCandidateAPIKey'],
            $heading, 
            $data, 
            $filters, 
            $subtitle, 
            $content
        );
    }
    
    /**
     * 
     * @param string $headings
     * @param string $subtitle
     * @param string $content
     * @param array[
     *   [
     *       "field" => "tag",
     *       "key" => "email",
     *       "relation" => "=",
     *       "value" => $agent['agent_email']
     *   ]
     * ] $filters;
     */
    public static function sendNotification($appId, $apiKey, $heading, $data, $filters, $subtitle = '', $content = '')
    {   
        if(empty(Yii::$app->params['inCodeception']))
            return true;
        
	$fields = [
            'app_id' => $appId,
            'filters' => $filters,
            'data' => $data,
            'contents' => ['en' => strip_tags($content)],
            'headings' => ['en' => strip_tags($heading)],
            'subtitle' => ['en' => strip_tags($subtitle)],
            'priority' => 10
            //"large_icon" => $comment['comment_by_photo'],
            //"android_group" => $groupId,
            //"collapse_id" => $groupId,
            //"android_group_message" => ["en" => "$[notif_count] new jobs"]
	];

	$fields = json_encode($fields);
    	// print("\nJSON sent:\n");
    	// print($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
            'Authorization: Basic '. $apiKey));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_exec($ch);
        curl_close($ch);

        /*print("\n\nJSON received:\n");
    	print_r($response);
    	print("\n");*/
    }
}
        