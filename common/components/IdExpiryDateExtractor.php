<?php

namespace common\components;

use Yii;
use Aws\Textract\TextractClient;
use Aws\Exception\AwsException;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * $dates = Yii::$app->idExpiryDateExtractor->extractExpiryDate($documentName);
 * echo "Expiry Date: " . $dates[1];
 */
class IdExpiryDateExtractor extends Component
{
    private $textractClient;

    public $version = 'latest';

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

        $key = is_string($this->key) ? trim($this->key) : '';
        $secret = is_string($this->secret) ? trim($this->secret) : '';

        if ($key === '' || $secret === '') {
            $this->textractClient = null;
            return;
        }

        try {
            $region = (isset(Yii::$app->resourceManager) && !empty(Yii::$app->resourceManager->region))
                ? Yii::$app->resourceManager->region
                : 'eu-west-2';

            $this->textractClient = new TextractClient([
                'region' => $region,
                'version' => $this->version,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret
                ]
            ]);
        } catch (\Throwable $e) {
            $this->textractClient = null;
        }
    }

    /**
     * @param $documentName
     * @return mixed|string
     */
    public function extractExpiryDate($documentName)
    {
        if ($this->textractClient === null) {
            return [
                "operation" => "error",
                "matches" => "Textract credentials not configured."
            ];
        }

        try {
            $result = $this->textractClient->detectDocumentText([
                'Document' => [
                    'S3Object' => [
                        'Bucket' => Yii::$app->resourceManager->bucket,
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

        } catch (\Throwable $e) {
            return [
                "operation" => "error",
                "matches" => $e->getMessage()
            ];
        }
    }
}




