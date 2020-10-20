<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use Aws\MediaConvert\MediaConvertClient;
use Aws\Exception\AwsException;


/**
 * MediaConvert handles resources to upload/uploaded to Amazon AWS
 *
 * Adjustments to the resource manager for BAWES usage
 *
 * @author Krushnkumar
 * @link http://www.bawes.net
 */
class MediaConvert extends Component
{
    const AUTH_VIA_KEY_AND_SECRET = 1;
    const AUTH_VIA_IAM_ROLE = 2;

    /**
     * @var string Auth Method
     */
    public $authMethod = self::AUTH_VIA_KEY_AND_SECRET;

    /**
     * @var string Amazon access key
     */
    public $key;

    /**
     * @var string Amazon secret access key
     */
    public $secret;

    /**
     * @var string AWS Region this endpoint belongs in
     */
    public $region;

    /**
     * @var string AWS service endpoint
     */
    public $endpoint;

    /**
     * @var string IAM role
     */
    public $role;

    public $jobQueue;

    /**
     * @var \Aws\S3\S3Client
     */
    private $_client;

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Fields required by default
        $requiredAttributes = ['region', 'endpoint', 'jobQueue', 'role'];

        // If Auth via Key and Secret, set vars as required
        if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
            $requiredAttributes = ['key', 'secret', 'region', 'endpoint', 'jobQueue', 'role'];
        }

        // Process Validation
        foreach ($requiredAttributes as $attribute) {
            if ($this->$attribute === null) {
                throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        parent::init();
    }

    /**
     * Returns a S3Client instance
     * @return \Aws\S3\S3Client
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $factoryParams = [
                'version' => 'latest',
                'region' => $this->region,
                'endpoint' => $this->endpoint
            ];

            // Use key and secret if its the auth method
            if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
                $factoryParams['credentials'] = [
                    'key' => $this->key,
                    'secret' => $this->secret,
                ];
            }

            $this->_client = new MediaConvertClient($factoryParams);
        }

        return $this->_client;
    }

    /**
     * get service endpoint
     * @return mixed
     */
    private function getEndpoint() {
        $factoryParams = [
            'version' => 'latest',
            'region' => $this->region
        ];

        // Use key and secret if its the auth method
        if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
            $factoryParams['credentials'] = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        }

        $client = new MediaConvertClient($factoryParams);

        //retrieve endpoint
        try {
            $result = $client->describeEndpoints([]);
        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            echo "\n";
        }

        return $result['Endpoints'][0]['Url'];
    }

    /**
     * Create a Job for AWS Elemental MediaConvert.
     *
     * This code expects that you have AWS credentials set up per:
     * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html
     */
    public function processVideo($source, $fileName)
    {
        $jobSetting = [
            "OutputGroups" => [
                [
                    "Name" => "File Group",
                    "OutputGroupSettings" => [
                        "Type" => "FILE_GROUP_SETTINGS",
                        "FileGroupSettings" => [
                            "Destination" => "s3://" . Yii::$app->resourceManager->bucket . "/candidate-video/" . $fileName
                        ]
                    ],
                    "Outputs" => [
                        [
                            "VideoDescription" => [
                                "ScalingBehavior" => "DEFAULT",
                                "TimecodeInsertion" => "DISABLED",
                                "AntiAlias" => "ENABLED",
                                "Sharpness" => 50,
                                "CodecSettings" => [
                                    "Codec" => "H_264",
                                    "H264Settings" => [
                                        "InterlaceMode" => "PROGRESSIVE",
                                        "NumberReferenceFrames" => 3,
                                        "Syntax" => "DEFAULT",
                                        "Softness" => 0,
                                        "GopClosedCadence" => 1,
                                        "GopSize" => 90,
                                        "Slices" => 1,
                                        "GopBReference" => "DISABLED",
                                        "SlowPal" => "DISABLED",
                                        "SpatialAdaptiveQuantization" => "ENABLED",
                                        "TemporalAdaptiveQuantization" => "ENABLED",
                                        "FlickerAdaptiveQuantization" => "DISABLED",
                                        "EntropyEncoding" => "CABAC",
                                        "Bitrate" => 5000000,
                                        "FramerateControl" => "SPECIFIED",
                                        "RateControlMode" => "CBR",
                                        "CodecProfile" => "MAIN",
                                        "Telecine" => "NONE",
                                        "MinIInterval" => 0,
                                        "AdaptiveQuantization" => "HIGH",
                                        "CodecLevel" => "AUTO",
                                        "FieldEncoding" => "PAFF",
                                        "SceneChangeDetect" => "ENABLED",
                                        "QualityTuningLevel" => "SINGLE_PASS",
                                        "FramerateConversionAlgorithm" => "DUPLICATE_DROP",
                                        "UnregisteredSeiTimecode" => "DISABLED",
                                        "GopSizeUnits" => "FRAMES",
                                        "ParControl" => "SPECIFIED",
                                        "NumberBFramesBetweenReferenceFrames" => 2,
                                        "RepeatPps" => "DISABLED",
                                        "FramerateNumerator" => 30,
                                        "FramerateDenominator" => 1,
                                        "ParNumerator" => 1,
                                        "ParDenominator" => 1
                                    ]
                                ],
                                "AfdSignaling" => "NONE",
                                "DropFrameTimecode" => "ENABLED",
                                "RespondToAfd" => "NONE",
                                "ColorMetadata" => "INSERT"
                            ],
                            "AudioDescriptions" => [
                                [
                                    "AudioTypeControl" => "FOLLOW_INPUT",
                                    "CodecSettings" => [
                                        "Codec" => "AAC",
                                        "AacSettings" => [
                                            "AudioDescriptionBroadcasterMix" => "NORMAL",
                                            "RateControlMode" => "CBR",
                                            "CodecProfile" => "LC",
                                            "CodingMode" => "CODING_MODE_2_0",
                                            "RawFormat" => "NONE",
                                            "SampleRate" => 48000,
                                            "Specification" => "MPEG4",
                                            "Bitrate" => 64000
                                        ]
                                    ],
                                    "LanguageCodeControl" => "FOLLOW_INPUT",
                                    "AudioSourceName" => "Audio Selector 1"
                                ]
                            ],
                            "ContainerSettings" => [
                                "Container" => "MP4",
                                "Mp4Settings" => [
                                    "CslgAtom" => "INCLUDE",
                                    "FreeSpaceBox" => "EXCLUDE",
                                    "MoovPlacement" => "PROGRESSIVE_DOWNLOAD"
                                ]
                            ],
                            "NameModifier" => "_1"
                        ]
                    ]
                ]
            ],
            "AdAvailOffset" => 0,
            "Inputs" => [
                [
                    "AudioSelectors" => [
                        "Audio Selector 1" => [
                            "Offset" => 0,
                            "DefaultSelection" => "NOT_DEFAULT",
                            "ProgramSelection" => 1,
                            "SelectorType" => "TRACK",
                            "Tracks" => [
                                1
                            ]
                        ]
                    ],
                    "VideoSelector" => [
                        "ColorSpace" => "FOLLOW"
                    ],
                    "FilterEnable" => "AUTO",
                    "PsiControl" => "USE_PSI",
                    "FilterStrength" => 0,
                    "DeblockFilter" => "DISABLED",
                    "DenoiseFilter" => "DISABLED",
                    "TimecodeSource" => "EMBEDDED",
                    "FileInput" => "s3://" . $source
                ]
            ],
            "TimecodeConfig" => [
                "Source" => "EMBEDDED"
            ]
        ];

        //try {
            return $this->getClient()->createJob([
                "Role" => $this->role,
                "Settings" => $jobSetting, //JobSettings structure
                "Queue" => $this->jobQueue,
                "UserMetadata" => [
                    "User" => isset(Yii::$app->user)? Yii::$app->user->getId(): null
                ]
            ]);

        /*} catch (AwsException $e) {

            // output error message if fails
            echo $e->getMessage();
            echo "\n";
        }*/
    }
}
