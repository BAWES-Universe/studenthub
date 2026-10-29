<?php

namespace common\components;

use common\models\Company;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Streams the licence stored on a company the caller can already view.
 * The request does not choose a bucket, key, or URL.
 */
class CompanyLicenceReader
{
    /**
     * @param Company $company
     * @return Response
     * @throws NotFoundHttpException
     */
    public static function send(Company $company)
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $headers = $response->headers;
        $headers->set('Cache-Control', 'private, no-store');
        $headers->set('Pragma', 'no-cache');
        $headers->set('X-Content-Type-Options', 'nosniff');

        $key = trim((string) $company->commercial_licence);
        $class = Company::classifyStoredDocument($key, 'licence');
        if (!in_array($class, ['permanent', 'public-copy'], true)) {
            throw new NotFoundHttpException('The licence file is unavailable. Upload a replacement.');
        }

        try {
            $file = Yii::$app->resourceManager->readStoredObject($key);
        } catch (\Throwable $e) {
            throw new NotFoundHttpException('The licence file is unavailable. Upload a replacement.');
        }

        $bytes = isset($file['bytes']) ? (string) $file['bytes'] : '';
        if ($bytes === '') {
            throw new NotFoundHttpException('The licence file is unavailable. Upload a replacement.');
        }

        $type = isset($file['contentType']) ? (string) $file['contentType'] : 'application/octet-stream';
        if (!preg_match('#^(image/(jpeg|png|gif|webp)|application/pdf)$#', $type)) {
            $type = 'application/octet-stream';
        }
        $name = basename(str_replace('\\', '/', $key));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '', $name);
        if ($name === '') {
            $name = 'commercial-licence';
        }

        $headers->set('Content-Type', $type);
        $headers->set('Content-Disposition', 'inline; filename="' . $name . '"');
        $response->content = $bytes;

        return $response;
    }
}
