<?php

namespace common\controllers;

use Aws\Exception\AwsException;
use Aws\Sts\StsClient;
use common\components\S3ResourceManager;
use DateTimeInterface;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\ServiceUnavailableHttpException;

/**
 * Provides shared AWS upload credential endpoints for each application module.
 */
class BaseAwsController extends Controller
{
    private const DEFAULT_SESSION_DURATION_SECONDS = 3600;
    private const MIN_SESSION_DURATION_SECONDS = 900;
    private const MAX_SESSION_DURATION_SECONDS = 3600;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Remove the default authenticator first so CORS preflight can run.
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];

        return $actions;
    }

    /**
     * Returns temporary credentials for direct uploads to the temporary bucket.
     *
     * @return array
     */
    public function actionConfig()
    {
        $credentials = $this->createTemporaryCredentials();
        $expiration = $this->formatExpiration($credentials['Expiration'] ?? null);

        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return [
            'region' => Yii::$app->temporaryBucketResourceManager->region,
            'key' => $credentials['AccessKeyId'],
            'secret' => $credentials['SecretAccessKey'],
            'accessKeyId' => $credentials['AccessKeyId'],
            'secretAccessKey' => $credentials['SecretAccessKey'],
            'sessionToken' => $credentials['SessionToken'],
            'expiration' => $expiration,
            'bucket' => Yii::$app->temporaryBucketResourceManager->bucket
        ];
    }

    /**
     * Creates temporary AWS credentials through STS.
     *
     * @return array
     */
    protected function createTemporaryCredentials()
    {
        $client = new StsClient($this->getStsClientConfig());
        $roleArn = Yii::$app->params['aws_temp_role_arn'] ?? '';
        $durationSeconds = $this->getSessionDurationSeconds();

        if ($roleArn === '') {
            Yii::error('AWS temporary upload role ARN is not configured.', __METHOD__);
            throw new ServiceUnavailableHttpException('Unable to issue upload credentials.');
        }

        try {
            $result = $client->assumeRole([
                'RoleArn' => $roleArn,
                'RoleSessionName' => 'studenthub-upload-' . gmdate('YmdHis'),
                'DurationSeconds' => $durationSeconds,
            ]);
        } catch (AwsException $exception) {
            Yii::error($exception, __METHOD__);
            throw new ServiceUnavailableHttpException('Unable to issue upload credentials.');
        }

        return $result->get('Credentials');
    }

    /**
     * Builds the STS client configuration from the temporary bucket manager.
     *
     * @return array
     */
    protected function getStsClientConfig()
    {
        $resourceManager = Yii::$app->temporaryBucketResourceManager;
        $config = [
            'version' => 'latest',
            'region' => $resourceManager->region
        ];

        if ($resourceManager->authMethod == S3ResourceManager::AUTH_VIA_KEY_AND_SECRET) {
            $config['credentials'] = [
                'key' => $resourceManager->key,
                'secret' => $resourceManager->secret,
            ];
        }

        return $config;
    }

    /**
     * Returns the configured STS session duration clamped to a short-lived range.
     *
     * @return int
     */
    protected function getSessionDurationSeconds()
    {
        $durationSeconds = (int) (Yii::$app->params['aws_temp_session_duration_seconds'] ?? self::DEFAULT_SESSION_DURATION_SECONDS);

        if ($durationSeconds <= 0) {
            return self::DEFAULT_SESSION_DURATION_SECONDS;
        }

        if ($durationSeconds < self::MIN_SESSION_DURATION_SECONDS) {
            return self::MIN_SESSION_DURATION_SECONDS;
        }

        if ($durationSeconds > self::MAX_SESSION_DURATION_SECONDS) {
            return self::MAX_SESSION_DURATION_SECONDS;
        }

        return $durationSeconds;
    }

    /**
     * Formats an AWS expiration value for the JSON response.
     *
     * @param mixed $expiration
     * @return string|null
     */
    protected function formatExpiration($expiration)
    {
        if ($expiration instanceof DateTimeInterface) {
            return $expiration->format(DATE_ATOM);
        }

        if ($expiration === null) {
            return null;
        }

        return (string) $expiration;
    }
}
