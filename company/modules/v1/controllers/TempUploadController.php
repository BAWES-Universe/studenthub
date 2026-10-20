<?php

namespace company\modules\v1\controllers;

use common\components\ActivationClientAddress;
use common\components\ActivationPresignAuthorizer;
use common\components\ActivationPresignDeniedException;
use common\components\ActivationPresignInputException;
use common\components\ActivationPresignLimitedException;
use common\components\ActivationPresignUnavailableException;
use common\components\TempUploadPresigner;
use common\components\TempUploadValidationException;
use company\models\Contact;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\TooManyRequestsHttpException;

/**
 * Employer temp-upload URLs.
 *
 * Logged-in uploads use the existing Employer bearer token.
 * Activation logo and licence uploads validate contact_auth_key,
 * contact_email, and company_id without consuming the activation key.
 * Do not add either action to AwsController.
 */
class TempUploadController extends BaseController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['except'] = ['activate', 'options'];

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
     * POST /v1/temp-upload/activate
     *
     * @return array
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws TooManyRequestsHttpException
     * @throws HttpException
     */
    public function actionActivate()
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');

        $body = Yii::$app->request->getBodyParams();
        $authorizer = new ActivationPresignAuthorizer(
            Yii::$app->has('cache') ? Yii::$app->cache : null,
            function ($email) {
                return Contact::find()->andWhere(['contact_email' => $email])->all();
            }
        );

        try {
            $authorizer->authorize(
                isset($body['contact_email']) ? $body['contact_email'] : null,
                isset($body['contact_auth_key']) ? $body['contact_auth_key'] : null,
                isset($body['company_id']) ? $body['company_id'] : null,
                ActivationClientAddress::resolve(
                    Yii::$app->request->getRemoteIP(),
                    Yii::$app->request->headers->get('X-Forwarded-For', '')
                )
            );
        } catch (ActivationPresignInputException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (ActivationPresignDeniedException $e) {
            throw new NotFoundHttpException($e->getMessage());
        } catch (ActivationPresignLimitedException $e) {
            throw new TooManyRequestsHttpException($e->getMessage());
        } catch (ActivationPresignUnavailableException $e) {
            Yii::error('Activation upload authorization is unavailable.', __METHOD__);
            throw new HttpException(503, 'Temporary upload is unavailable.');
        }

        return $this->presign($body);
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
