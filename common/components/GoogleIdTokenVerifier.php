<?php

namespace common\components;

use Yii;
use yii\base\Component;

class GoogleIdTokenVerifier extends Component
{
    public $allowedClientIds = [];
    public $tokenInfoEndpoint = 'https://www.googleapis.com/oauth2/v3/tokeninfo';
    public $timeout = 10;

    public function init()
    {
        parent::init();

        if (is_string($this->allowedClientIds)) {
            $this->allowedClientIds = $this->parseClientIds($this->allowedClientIds);
        }

        if (!$this->allowedClientIds) {
            $this->allowedClientIds = $this->parseClientIds(getenv('GOOGLE_OAUTH_CLIENT_IDS') ?: '');
        }
    }

    public function verify($idToken)
    {
        $idToken = trim((string)$idToken);

        if ($idToken === '') {
            return null;
        }

        if (!$this->allowedClientIds) {
            Yii::warning('GOOGLE_OAUTH_CLIENT_IDS is not configured; rejecting Google login.', __METHOD__);
            return null;
        }

        $response = $this->fetchTokenInfo($idToken);

        if (!$response || !$this->hasValidClaims($response)) {
            return null;
        }

        return $response;
    }

    private function fetchTokenInfo($idToken)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tokenInfoEndpoint . '?' . http_build_query(['id_token' => $idToken]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || $httpCode !== 200) {
            Yii::warning('Google tokeninfo request failed: HTTP ' . $httpCode . ($curlError ? ' - ' . $curlError : ''), __METHOD__);
            return null;
        }

        $response = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE || !is_object($response)) {
            Yii::warning('Google tokeninfo response was not valid JSON.', __METHOD__);
            return null;
        }

        return $response;
    }

    private function hasValidClaims($response)
    {
        if (empty($response->email) || empty($response->aud) || empty($response->iss) || empty($response->exp)) {
            return false;
        }

        if (!in_array($response->aud, $this->allowedClientIds, true)) {
            return false;
        }

        if (!in_array($response->iss, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return false;
        }

        if ((int)$response->exp <= time()) {
            return false;
        }

        return $this->isEmailVerified($response->email_verified ?? null);
    }

    private function isEmailVerified($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string)$value), ['1', 'true'], true);
    }

    private function parseClientIds($value)
    {
        return array_values(array_filter(array_map('trim', explode(',', (string)$value))));
    }
}
