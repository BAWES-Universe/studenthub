<?php

namespace staff\modules\v1\controllers;

use common\components\TempUploadPresigner;
use common\components\TempUploadValidationException;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;

/**
 * Staff-only authenticated temp-upload URL endpoint.
 *
 * Intentionally separate from AwsController. Do not add this action to
 * AwsController. Existing unauthenticated GET /v1/aws/config must remain unchanged.
 */
class TempUploadController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
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

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
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
     * POST /v1/temp-upload/url
     *
     * @return array
     * @throws BadRequestHttpException
     * @throws HttpException
     */
    public function actionUrl()
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');

        $body = Yii::$app->request->getBodyParams();

        try {
            return (new TempUploadPresigner())->presign(
                isset($body['filename']) ? $body['filename'] : null,
                isset($body['content_type']) ? $body['content_type'] : null,
                isset($body['file_size']) ? $body['file_size'] : null
            );
        } catch (TempUploadValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable) {
            // Do not log the throwable: SDK failures can include signer material or the URL.
            Yii::error('Temporary upload presign failed.', __METHOD__);
            throw new HttpException(503, 'Temporary upload is unavailable.');
        }
    }
}
