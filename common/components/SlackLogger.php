<?php
namespace common\components;

use Yii;
use understeam\slack\LogTarget;
use yii\log\Logger;

class SlackLogger extends LogTarget
{
    public function getLevelColor($level)
    {
        $colors = [
            Logger::LEVEL_ERROR => 'danger',
            Logger::LEVEL_WARNING => 'warning',
            Logger::LEVEL_INFO => 'good',
            Logger::LEVEL_PROFILE => 'warning',
            Logger::LEVEL_TRACE => 'warning',
        ];
        if (!isset($colors[$level])) {
            return 'good';
        }
        return $colors[$level];
    }
    
    public function getAttachments()
    {
        $attachments = [];
        foreach ($this->messages as $i => $message) {
            
            //The actual logged message
            $logMessage = $message[0];
            
            //Title of the logged message goes between [brackets] - isolate it from message along with content
            preg_match_all("/\[[^\]]*\]/", $logMessage, $matches);
            $title = $matches[0][0];
            $finalTitle = str_replace(['[',']'],"",$matches[0][0]);
            $finalContent = str_replace($title, "", $logMessage);
            
            //The class/method that triggered the log
            $classMethod = $message[2];
            
            $attachments[] = [
                'fallback' => $logMessage,
                'title' => $finalTitle,
                'text' => $finalContent,
                'color' => $this->getLevelColor($message[1]),
            ];
        }
        return $attachments;
    }
    
    /**
     * Exports log [[messages]] to a specific destination.
     * Child classes must implement this method.
     */
    public function export()
    {
        $this->slack->send(Yii::$app->formatter->asDatetime(new \DateTime("now")), $this->emoji, $this->getAttachments());
    }
}