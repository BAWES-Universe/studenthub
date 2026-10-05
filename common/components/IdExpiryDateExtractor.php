<?php

namespace common\components;

use Yii;
use Aws\Textract\TextractClient;
use Aws\Exception\AwsException;
use yii\base\Component;

/**
 * $dates = Yii::$app->idExpiryDateExtractor->extractExpiryDate($documentName);
 * echo "Expiry Date: " . $dates[1];
 */
class IdExpiryDateExtractor extends Component
{
    private $textractClient;
    private $configurationError;

    public $version = 'latest';
    public $region;
    public $bucket;

    /**
     * @var string Amazon access key
     */
    public $key;

    /**
     * @var string Amazon secret access key
     */
    public $secret;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $region = $this->region ?: $this->getResourceManagerProperty('region');
        if (!$region || !$this->bucket && !$this->getResourceManagerProperty('bucket')) {
            $this->configurationError = 'Textract document bucket configuration is missing.';
            Yii::warning($this->configurationError, __METHOD__);
            return;
        }

        if (!$this->key || !$this->secret) {
            $this->configurationError = 'Textract credentials are not configured.';
            Yii::warning($this->configurationError, __METHOD__);
            return;
        }

        $this->textractClient = new TextractClient([
            'region' => $region,
            'version' => $this->version,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret
            ]
        ]);
    }

    /**
     * @param $documentName
     * @return mixed|string
     */
    public function extractExpiryDate($documentName)
    {
        if ($this->configurationError) {
            return $this->errorResponse('Textract is not configured.');
        }

        if (!$this->isSafeDocumentName($documentName)) {
            Yii::warning("Rejected unsafe Textract document name.", __METHOD__);
            return $this->errorResponse('Invalid document reference.');
        }

        $bucket = $this->bucket ?: $this->getResourceManagerProperty('bucket');
        if (!$bucket) {
            Yii::warning("Textract document bucket is not configured.", __METHOD__);
            return $this->errorResponse('Textract is not configured.');
        }

        try {
            $result = $this->textractClient->detectDocumentText([
                'Document' => [
                    'S3Object' => [
                        'Bucket' => $bucket,
                        'Name' => $documentName
                    ]
                ]
            ]);

            $blocks = $result['Blocks'];
            $datePattern = '/([0-2][0-9]|3[01])\/(0[1-9]|1[0-2])\/(19|20)\d\d/'; //DD/MM/YYYY
            //$datePattern = '/(0[1-9]|1[0-2])\/([0-2][0-9]|3[01])\/(19|20)\d\d/';//MM/DD/YYYY

            $dates = [];

            foreach ($blocks as $block) {
                if ($block['BlockType'] == 'LINE') {
                    if (preg_match($datePattern, $block['Text'], $matches)) {
                        $dates[] = $matches[0];
                    }
                }
            }

            $idPattern = '/^\d+$/'; //DD/MM/YYYY

            $ids = [];

            foreach ($blocks as $block) {
                if ($block['BlockType'] == 'LINE') {
                    if (
                        preg_match($idPattern, $block['Text'], $matches) &&
                        strlen($matches[0]) > 5
                    ) {
                        $ids[] = $matches[0];
                    }
                }
            }

            if(sizeof($dates) > 0 || sizeof($ids) > 0) {
                return [
                    "operation" => "success",
                    "matches" => $dates,
                    "ids" => $ids
                ];
            }

            return [
                "operation" => "error",
                "matches" => "Expiry Date not found."
            ];

        } catch (AwsException $e) {
            Yii::error(sprintf(
                'Textract detectDocumentText failed: %s',
                $e->getAwsErrorCode() ?: get_class($e)
            ), __METHOD__);

            return $this->errorResponse('Unable to read document text.');
        } catch (\Throwable $e) {
            Yii::error(sprintf(
                'Textract expiry extraction failed: %s',
                get_class($e)
            ), __METHOD__);

            return $this->errorResponse('Unable to read document text.');
        }
    }

    private function getResourceManagerProperty($property)
    {
        $resourceManager = Yii::$app->get('resourceManager', false);

        return $resourceManager && isset($resourceManager->$property)
            ? $resourceManager->$property
            : null;
    }

    private function isSafeDocumentName($documentName)
    {
        if (!is_string($documentName) || $documentName === '') {
            return false;
        }

        if (preg_match('/(^\/|^\w+:|\\\\|\.\.)/', $documentName)) {
            return false;
        }

        return str_starts_with($documentName, 'photos/');
    }

    private function errorResponse($message)
    {
        return [
            "operation" => "error",
            "matches" => $message
        ];
    }
}




