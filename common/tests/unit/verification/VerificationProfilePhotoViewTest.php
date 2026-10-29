<?php

namespace common\tests\unit\verification;

use common\components\S3ResourceManager;
use common\models\Candidate;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;

/**
 * The QR verification page must render the canonical profile photo URL.
 * These tests render the view. They do not fetch the image bytes.
 */
class VerificationProfilePhotoViewTest extends TestCase
{
    /** @var Application|null */
    private $previousApp;

    /** @var array */
    private $previousAliases = [];

    /** @var Application|null */
    private $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureFramework();
        $this->previousApp = Yii::$app;
        $this->previousAliases = Yii::$aliases;
        $this->application = $this->createApplication('verification-photo-view-test', $this->fixtureComponents());
        $this->createCandidateTable();
    }

    protected function tearDown(): void
    {
        $this->releaseApplication($this->application);
        $this->application = null;
        Yii::$app = $this->previousApp;
        Yii::$aliases = $this->previousAliases;
        $this->previousApp = null;
        $this->previousAliases = [];
        parent::tearDown();
    }

    public function testCurrentProfilePhotoKeyRendersUnsignedS3Url()
    {
        $this->useRealAnonymousUploadsManager();
        $candidate = $this->candidate('candidate-profile-photos/unit-test.jpg');

        $html = $this->render($candidate, null);

        $url = $candidate->getPersonalPhotoUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('candidate-profile-photos/unit-test.jpg', $url);
        $this->assertStringNotContainsString('X-Amz-', $url);
        $this->assertStringContainsString($url, $html);
        $this->assertStringNotContainsString('res.cloudinary.com', $html);
        $this->assertStringNotContainsString('v1596453482', $html);
        $this->assertSame('candidate-profile-photos/unit-test.jpg', $candidate->candidate_personal_photo);
    }

    public function testLegacyPhotosPrefixRendersPermanentObjectUrl()
    {
        $this->useRealAnonymousUploadsManager();
        $candidate = $this->candidate('photos/legacy.jpg');

        $html = $this->render($candidate, null);

        $url = $candidate->getPersonalPhotoUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('photos/legacy.jpg', $url);
        $this->assertStringNotContainsString('res.cloudinary.com', $html);
        $this->assertStringContainsString($url, $html);
        $this->assertSame('photos/legacy.jpg', $candidate->candidate_personal_photo);
    }

    public function testBareFilenameStillUsesLegacyCloudinaryResolver()
    {
        $this->useRealAnonymousUploadsManager();
        Yii::$app->set('cloudinaryManager', new class extends \yii\base\Component {
            public $cloud_name = 'test-cloud';
            public function getUrl($publicId, $type = 'image')
            {
                return null;
            }
        });

        $candidate = $this->candidate('legacy-bare.jpg');
        $html = $this->render($candidate, null);
        $url = $candidate->getPersonalPhotoUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('res.cloudinary.com/test-cloud/image/upload/', $url);
        $this->assertStringContainsString('legacy-bare.jpg', $url);
        $this->assertStringNotContainsString('v1596453482', $url);
        $this->assertStringContainsString($url, $html);
        $this->assertSame('legacy-bare.jpg', $candidate->candidate_personal_photo);
    }

    public function testMissingPhotoRendersPlaceholderWithoutClearingStoredValue()
    {
        $this->useRealAnonymousUploadsManager();

        $empty = $this->candidate('');
        $emptyHtml = $this->render($empty, null);
        $this->assertStringContainsString('no_image.png', $emptyHtml);
        $this->assertStringNotContainsString('<img onerror=', $emptyHtml);
        $this->assertSame('', $empty->candidate_personal_photo);

        $unavailable = $this->candidate('candidate-profile-photos/missing.jpg');
        $html = $this->render($unavailable, null);
        $this->assertStringContainsString('no_image.png', $html);
        $this->assertStringContainsString('this.onerror=null', $html);
        $this->assertStringNotContainsString('candidate_personal_photo', $html);
        $this->assertSame('candidate-profile-photos/missing.jpg', $unavailable->candidate_personal_photo);
    }

    public function testReplacingTheStoredKeyChangesTheNextRender()
    {
        $this->useRealAnonymousUploadsManager();
        $candidate = $this->candidate('candidate-profile-photos/first.jpg');

        $first = $this->render($candidate, null);
        $this->assertStringContainsString('candidate-profile-photos/first.jpg', $first);

        $candidate->candidate_personal_photo = 'candidate-profile-photos/second.jpg';
        $second = $this->render($candidate, null);

        $this->assertStringContainsString('candidate-profile-photos/second.jpg', $second);
        $this->assertStringNotContainsString('candidate-profile-photos/first.jpg', $second);
    }

    public function testValidityBannerStaysIndependentOfThePhoto()
    {
        $this->useRealAnonymousUploadsManager();
        $candidate = $this->candidate('candidate-profile-photos/unit-test.jpg');

        $invalid = $this->render($candidate, null);
        $this->assertStringContainsString('هوية غير صالحه', $invalid);
        $this->assertStringContainsString('candidate-profile-photos/unit-test.jpg', $invalid);

        $card = (object) [
            'updated_at' => '2026-01-15',
            'expiry_date' => '2026-04-15',
        ];
        $valid = $this->render($candidate, $card);
        $this->assertStringContainsString('هوية صالحه', $valid);
        $this->assertStringNotContainsString('هوية غير صالحه', $valid);
        $this->assertStringContainsString('candidate-profile-photos/unit-test.jpg', $valid);
    }

    public function testViewSourceNoLongerHardcodesCloudinary()
    {
        $source = file_get_contents(Yii::getAlias('@verification/views/site/index.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString('res.cloudinary.com/studenthub/image/upload', $source);
        $this->assertStringContainsString('getPersonalPhotoUrl()', $source);
    }

    public function testIssuedCardQrStillTargetsTheVerificationHostAndCandidateUid()
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/staff/modules/v1/controllers/CandidateIdCardController.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('https://v.studenthub.co/', $source);
        $this->assertStringContainsString('$path . $model->candidate->candidate_uid', $source);
        $rules = file_get_contents(dirname(__DIR__, 4) . '/verification/config/main.php');
        $this->assertStringContainsString("'<candidate_uid:[A-Za-z0-9\\_-]+>' => 'site/index'", $rules);
    }

    public function testConfiguredPhotoManagersStayUninstantiatedOnTheExistingApplication()
    {
        $fixtureApp = $this->application;
        $fixtureAliases = Yii::$aliases;
        $host = null;
        $nested = null;

        try {
            $host = $this->createApplication('verification-photo-host', [
                'request' => [
                    'cookieValidationKey' => 'verification-photo-host',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/index.php',
                ],
                'resourceManager' => [
                    'class' => S3ResourceManager::class,
                    'authMethod' => S3ResourceManager::AUTH_VIA_ANONYMOUS,
                    'region' => 'eu-west-2',
                    'bucket' => 'studenthub-uploads',
                ],
                'cloudinaryManager' => [
                    'class' => \common\components\CloudinaryManager::class,
                ],
            ]);
            $hostAliases = Yii::$aliases;
            $resourceDefinition = $host->getComponents(true)['resourceManager'];
            $cloudinaryDefinition = $host->getComponents(true)['cloudinaryManager'];
            $this->assertFalse($host->has('resourceManager', true));
            $this->assertFalse($host->has('cloudinaryManager', true));
            $this->assertFalse($host->has('db', false));

            $nested = $this->createApplication('verification-photo-nested', $this->fixtureComponents());
            $this->createCandidateTable();
            $this->useRealAnonymousUploadsManager();
            Yii::$app->set('cloudinaryManager', new \yii\base\Component());
            $html = $this->render($this->candidate('candidate-profile-photos/unit-test.jpg'), null);
            $this->assertNotSame($host, Yii::$app);
            $this->assertStringContainsString('candidate-profile-photos/unit-test.jpg', $html);

            $this->releaseApplication($nested);
            $nested = null;
            Yii::$app = $host;
            Yii::$aliases = $hostAliases;

            $this->assertSame($host, Yii::$app);
            $this->assertTrue($host->has('resourceManager', false));
            $this->assertFalse($host->has('resourceManager', true));
            $this->assertTrue($host->has('cloudinaryManager', false));
            $this->assertFalse($host->has('cloudinaryManager', true));
            $this->assertSame($resourceDefinition, $host->getComponents(true)['resourceManager']);
            $this->assertSame($cloudinaryDefinition, $host->getComponents(true)['cloudinaryManager']);
            $this->assertArrayNotHasKey('resourceManager', $host->getComponents(false));
            $this->assertArrayNotHasKey('cloudinaryManager', $host->getComponents(false));
            $this->assertFalse($host->has('db', false));
        } finally {
            if ($nested instanceof Application) {
                $this->releaseApplication($nested);
            }
            if ($host instanceof Application) {
                if (Yii::$app !== $host) {
                    Yii::$app = $host;
                }
                $this->releaseApplication($host);
            }
            Yii::$app = $fixtureApp;
            Yii::$aliases = $fixtureAliases;
            $this->application = $fixtureApp;
        }
    }

    private function ensureFramework(): void
    {
        if (class_exists(Yii::class, false)) {
            return;
        }

        $root = dirname(__DIR__, 4);
        require_once $root . '/vendor/autoload.php';
        require_once $root . '/vendor/yiisoft/yii2/Yii.php';
        require_once $root . '/common/config/bootstrap.php';

        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_ENV') or define('YII_ENV', 'test');
    }

    /**
     * @return array<string, mixed>
     */
    private function fixtureComponents(): array
    {
        $assetPath = sys_get_temp_dir() . '/sh-verify-assets';
        if (!is_dir($assetPath)) {
            mkdir($assetPath, 0775, true);
        }

        return [
            'db' => [
                'class' => \yii\db\Connection::class,
                'dsn' => 'sqlite::memory:',
            ],
            'request' => [
                'cookieValidationKey' => 'verification-photo-view-test',
                'scriptFile' => __FILE__,
                'scriptUrl' => '/index.php',
            ],
            'assetManager' => [
                'basePath' => $assetPath,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $components
     */
    private function createApplication(string $id, array $components): Application
    {
        $root = dirname(__DIR__, 4);

        return new Application([
            'id' => $id,
            'basePath' => $root . '/verification',
            'vendorPath' => $root . '/vendor',
            'components' => $components,
        ]);
    }

    private function createCandidateTable(): void
    {
        Yii::$app->db->createCommand(
            'CREATE TABLE candidate (
                candidate_id INTEGER PRIMARY KEY,
                candidate_name_ar TEXT,
                candidate_personal_photo TEXT,
                candidate_civil_id TEXT,
                store_id INTEGER,
                university_id INTEGER
            )'
        )->execute();
    }

    private function releaseApplication(?Application $application): void
    {
        if (!$application instanceof Application) {
            return;
        }

        if ($application->has('errorHandler', true)) {
            $application->getErrorHandler()->unregister();
        }
        if (Yii::$app === $application) {
            Yii::$app = null;
        }
    }

    private function useRealAnonymousUploadsManager(): void
    {
        Yii::$app->set('resourceManager', new S3ResourceManager([
            'authMethod' => S3ResourceManager::AUTH_VIA_ANONYMOUS,
            'region' => 'eu-west-2',
            'bucket' => 'studenthub-uploads',
        ]));
    }

    private function candidate(string $photo): Candidate
    {
        $candidate = new Candidate();
        $candidate->candidate_name_ar = 'Test Candidate';
        $candidate->candidate_personal_photo = $photo;
        $candidate->candidate_civil_id = '000';
        $candidate->populateRelation('university', null);
        $candidate->populateRelation('store', null);
        $candidate->populateRelation('company', null);

        return $candidate;
    }

    /**
     * @param object|null $id
     */
    private function render(Candidate $candidate, $id): string
    {
        return Yii::$app->view->renderFile(
            Yii::getAlias('@verification/views/site/index.php'),
            [
                'candidate' => $candidate,
                'id' => $id,
            ]
        );
    }
}
