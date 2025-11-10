<?php

namespace common\helpers;

use Yii;

/**
 * Simple device detection helper to replace MobileDetect library
 * Uses User-Agent header from Yii request object
 */
class DeviceDetector
{
    /**
     * Detect if the device is mobile
     * @return bool
     */
    public function isMobile()
    {
        // Tablets are not mobile devices
        if ($this->isTablet()) {
            return false;
        }

        $userAgent = $this->getUserAgent();
        if (empty($userAgent)) {
            return false;
        }

        // Common mobile device patterns (excluding tablets)
        $mobilePatterns = [
            'iPhone', 'iPod', 'BlackBerry', 'Windows Phone', 
            'Opera Mini', 'IEMobile', 'Mobile Safari'
        ];

        foreach ($mobilePatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Android mobile devices have "Mobile" in user agent
        if (stripos($userAgent, 'Android') !== false && stripos($userAgent, 'Mobile') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Detect if the device is a tablet
     * @return bool
     */
    public function isTablet()
    {
        $userAgent = $this->getUserAgent();
        if (empty($userAgent)) {
            return false;
        }

        // Explicit tablet patterns (check these first)
        $explicitTabletPatterns = [
            'iPad', 'Tablet', 'PlayBook', 'Kindle', 
            'Silk', 'TouchPad', 'Nook', 'Windows RT'
        ];

        foreach ($explicitTabletPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Android tablets typically don't have "Mobile" in user agent
        // but have "Android" and are not mobile phones
        if (stripos($userAgent, 'Android') !== false && stripos($userAgent, 'Mobile') === false) {
            return true;
        }

        return false;
    }

    /**
     * Get the user agent string
     * @return string
     */
    public function getUserAgent()
    {
        if (isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            return Yii::$app->request->getUserAgent() ?: '';
        }
        
        // Fallback for console requests
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
}

