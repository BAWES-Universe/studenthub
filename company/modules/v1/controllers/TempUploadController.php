<?php

namespace company\modules\v1\controllers;

use common\components\TempUploadPresigner;
use common\components\TempUploadValidationException;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;

/**
 * Employer temp-upload URLs.
 *
 * Logged-in uploads use the existing Employer bearer token.
 * Licence and logo uploads are not accepted here.
 * Do not add this action to AwsController.
 */
class TempUploadController extends BaseController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
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

        return $this->presign(Yii::$app->request->getBodyParams());
    }

    /**
     * @param array $body
     * @return array
     * @throws BadRequestHttpException
     * @throws HttpException
     */
    private function presign($body)
    {
        try {
            $presigner = new TempUploadPresigner(
                null,
                null,
                TempUploadPresigner::COMPANY_MAX_FILE_SIZE,
                'Employer'
            );

            return $presigner->presign(
                isset($body['filename']) ? $body['filename'] : null,
                isset($body['content_type']) ? $body['content_type'] : null,
                isset($body['file_size']) ? $body['file_size'] : null
            );
        } catch (TempUploadValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Throwable) {
            Yii::error('Temporary upload presign failed.', __METHOD__);
            throw new HttpException(503, 'Temporary upload is unavailable.');
        }
    }
}
