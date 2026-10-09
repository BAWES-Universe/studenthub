<?php

namespace common\tests\unit\components;

use common\components\CandidateVideoState;
use PHPUnit\Framework\TestCase;
use yii\base\Component;
use yii\base\Module;
use yii\db\Connection;
use yii\i18n\MessageSource;
use yii\web\Application;
use yii\web\IdentityInterface;

require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 4) . '/common/config/bootstrap.php';

class CandidateVideoControllerTest extends TestCase
{
    /** @var \candidate\modules\v1\controllers\AccountController */
    private $controller;

    /** @var CandidateVideoStorageStub */
    private $storage;

    public static function setUpBeforeClass(): void
    {
        if (!defined('YII_ENV')) {
            define('YII_ENV', 'test');
        }
        if (!defined('YII_DEBUG')) {
            define('YII_DEBUG', true);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!getenv('CE37_TEST_DB_HOST') || !getenv('CE37_TEST_DB_PASSWORD')) {
            $this->markTestSkipped('Disposable database is not configured.');
        }

        $this->boot();
        $this->resetRows();
    }

    protected function tearDown(): void
    {
        if (\Yii::$app !== null) {
            \Yii::$app = null;
        }
        parent::tearDown();
    }

    public function testSuccessfulRemovalClearsTheJobAndStorageFailureDoesNot()
    {
        $this->insertPending('stuck-output_1', 'stuck-job');
        $this->storage->fail = true;

        $failed = $this->controller->actionRemoveVideo();

        $this->assertSame('error', $failed['operation']);
        $this->assertSame(['candidate-video/stuck-output_1.jpg'], $this->storage->deleted);
        $row = $this->row();
        $this->assertSame('stuck-output_1', $row['candidate_video']);
        $this->assertSame('stuck-job', $row['candidate_video_job_id']);
        $this->assertSame('0', (string)$row['candidate_video_processed']);
        $this->assertSame('Noura', $row['candidate_name']);

        $this->storage->fail = false;
        $this->storage->deleted = [];
        $removed = $this->controller->actionRemoveVideo();

        $this->assertSame('success', $removed['operation'], isset($removed['message']) ? json_encode($removed['message']) : '');
        $this->assertSame([
            'candidate-video/stuck-output_1.jpg',
            'candidate-video/stuck-output_1.mp4',
        ], $this->storage->deleted);
        $this->assertNull($removed['candidate_video']);
        $row = $this->row();
        $this->assertNull($row['candidate_video']);
        $this->assertNull($row['candidate_video_job_id']);
        $this->assertSame('1', (string)$row['candidate_video_processed']);
        $this->assertSame('Noura', $row['candidate_name']);
        $this->assertSame('noura@example.test', $row['candidate_email']);
    }

    public function testCurrentJobCallbackWritesAndAReplacedJobDoesNot()
    {
        $this->insertPending('stuck-output_1', 'job-current');
        $this->postDetail($this->detail('job-current', 'PROGRESSING'));

        $progress = $this->controller->actionVideoByWebhook();

        $this->assertSame('success', $progress['operation']);
        $row = $this->row();
        $this->assertSame('stuck-output_1', $row['candidate_video']);
        $this->assertSame('0', (string)$row['candidate_video_processed']);

        $this->postDetail($this->completeDetail('job-current', 's3://bucket/candidate-video/converted_1.mp4'));
        $completed = $this->controller->actionVideoByWebhook();

        $this->assertSame('success', $completed['operation']);
        $row = $this->row();
        $this->assertSame('converted_1', $row['candidate_video']);
        $this->assertSame('1', (string)$row['candidate_video_processed']);
        $this->assertSame('job-current', $row['candidate_video_job_id']);
        $this->assertSame('Noura', $row['candidate_name']);

        $this->insertPending('stuck-output_1', 'job-current');
        $loaded = \candidate\models\Candidate::find()->andWhere(['candidate_video_job_id' => 'job-current'])->one();
        $this->assertNotNull($loaded);
        \Yii::$app->db->createCommand()->update('candidate', [
            'candidate_video' => 'replacement_1',
            'candidate_video_job_id' => 'replacement-job',
            'candidate_video_processed' => 1,
        ], ['candidate_id' => 17])->execute();

        $method = new \ReflectionMethod($this->controller, 'writeCurrentVideoJob');
        $method->setAccessible(true);
        $written = $method->invoke(
            $this->controller,
            $loaded,
            'job-current',
            CandidateVideoState::completedJobAttributes('s3://bucket/candidate-video/stuck-output_1.mp4')
        );

        $this->assertFalse($written);
        $row = $this->row();
        $this->assertSame('replacement_1', $row['candidate_video']);
        $this->assertSame('replacement-job', $row['candidate_video_job_id']);
        $this->assertSame('1', (string)$row['candidate_video_processed']);
        $this->assertSame('Noura', $row['candidate_name']);
    }

    public function testCurrentJobErrorClearsTheVideoWithoutTouchingAReplacement()
    {
        $this->insertPending('stuck-output_1', 'job-current');
        $this->postDetail($this->detail('job-current', 'ERROR', 'conversion failed'));

        $failed = $this->controller->actionVideoByWebhook();

        $this->assertSame('error', $failed['operation']);
        $row = $this->row();
        $this->assertNull($row['candidate_video']);
        $this->assertSame('1', (string)$row['candidate_video_processed']);
        $this->assertSame('job-current', $row['candidate_video_job_id']);
        $this->assertSame('Noura', $row['candidate_name']);
    }

    private function boot()
    {
        if (\Yii::$app !== null) {
            \Yii::$app = null;
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
        $_SERVER['SCRIPT_NAME'] = '/index-test.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->storage = new CandidateVideoStorageStub();
        new Application([
            'id' => 'candidate-video-controller-test',
            'basePath' => dirname(__DIR__, 4) . '/common',
            'components' => [
                'db' => [
                    'class' => Connection::class,
                    'dsn' => 'mysql:host=' . getenv('CE37_TEST_DB_HOST') . ';dbname=studenthub_video',
                    'username' => 'root',
                    'password' => getenv('CE37_TEST_DB_PASSWORD'),
                    'charset' => 'utf8',
                ],
                'request' => [
                    'cookieValidationKey' => 'candidate-video-controller-test',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/index-test.php',
                    'enableCsrfValidation' => false,
                    'enableCookieValidation' => false,
                ],
                'user' => [
                    'identityClass' => CandidateVideoTestIdentity::class,
                    'enableSession' => false,
                    'enableAutoLogin' => false,
                    'loginUrl' => null,
                ],
                'resourceManager' => $this->storage,
                'i18n' => [
                    'translations' => [
                        '*' => [
                            'class' => IdentityMessageSource::class,
                        ],
                    ],
                ],
            ],
        ]);

        \Yii::$app->set('temporaryBucketResourceManager', new CandidateVideoStorageStub());
        \Yii::$app->set('eventManager', new CandidateVideoEventStub());
        \Yii::$app->user->setIdentity(new CandidateVideoTestIdentity(17));
        $this->controller = new \candidate\modules\v1\controllers\AccountController('account', new Module('v1'));
        $this->prepareSchema();
    }

    private function prepareSchema()
    {
        $db = \Yii::$app->db;
        $db->createCommand('DROP TABLE IF EXISTS candidate')->execute();
        $db->createCommand('CREATE TABLE candidate (
            candidate_id INT NOT NULL PRIMARY KEY,
            candidate_uid VARCHAR(20) NULL,
            candidate_name VARCHAR(255) NULL,
            candidate_name_ar VARCHAR(255) NULL,
            candidate_email VARCHAR(255) NULL,
            candidate_password_hash VARCHAR(255) NULL,
            candidate_birth_date DATE NULL,
            candidate_personal_photo VARCHAR(255) NULL,
            candidate_civil_id VARCHAR(32) NULL,
            candidate_civil_expiry_date DATE NULL,
            candidate_civil_photo_front VARCHAR(255) NULL,
            candidate_civil_photo_back VARCHAR(255) NULL,
            candidate_phone VARCHAR(32) NULL,
            candidate_gender INT NULL,
            candidate_objective VARCHAR(255) NULL,
            candidate_iban VARCHAR(70) NULL,
            bank_id INT NULL,
            candidate_driving_license INT NULL,
            candidate_mom_kuwaiti INT NULL,
            candidate_latitude DECIMAL(10,6) NULL,
            candidate_longitude DECIMAL(10,6) NULL,
            candidate_area_uuid VARCHAR(60) NULL,
            currency_code VARCHAR(3) NULL,
            university_id INT NULL,
            country_id INT NULL,
            candidate_video VARCHAR(255) NULL,
            candidate_video_job_id VARCHAR(255) NULL,
            candidate_video_processed TINYINT NULL,
            candidate_job_search_status INT NULL,
            is_incomplete_profile TINYINT NULL,
            candidate_pending_profile TEXT NULL,
            ip_address VARCHAR(64) NULL,
            deleted TINYINT NOT NULL DEFAULT 0,
            candidate_created_at DATETIME NULL,
            candidate_updated_at DATETIME NULL
        )')->execute();
        $db->createCommand('CREATE TABLE IF NOT EXISTS candidate_education (
            education_uuid VARCHAR(60) NOT NULL PRIMARY KEY,
            candidate_id INT NULL
        )')->execute();
        $db->createCommand('DROP TABLE IF EXISTS candidate_skill')->execute();
        $db->createCommand('CREATE TABLE candidate_skill (
            candidate_skill_id INT NOT NULL PRIMARY KEY,
            candidate_id INT NULL,
            deleted TINYINT NOT NULL DEFAULT 0
        )')->execute();
        $db->createCommand('CREATE TABLE IF NOT EXISTS area (
            area_uuid VARCHAR(60) NOT NULL PRIMARY KEY
        )')->execute();
        $db->createCommand('CREATE TABLE IF NOT EXISTS university (
            university_id INT NOT NULL PRIMARY KEY,
            university_name_en VARCHAR(255) NULL,
            deleted TINYINT NOT NULL DEFAULT 0
        )')->execute();
        $db->createCommand('CREATE TABLE IF NOT EXISTS country (
            country_id INT NOT NULL PRIMARY KEY,
            country_name_en VARCHAR(255) NULL
        )')->execute();
    }

    private function resetRows()
    {
        \Yii::$app->db->createCommand('DELETE FROM candidate')->execute();
    }

    private function insertPending($video, $jobId)
    {
        $this->resetRows();
        \Yii::$app->db->createCommand()->insert('candidate', [
            'candidate_id' => 17,
            'candidate_uid' => 'uid-17',
            'candidate_name' => 'Noura',
            'candidate_name_ar' => 'نورة',
            'candidate_email' => 'noura@example.test',
            'candidate_password_hash' => 'hash',
            'candidate_birth_date' => '2006-01-15',
            'candidate_personal_photo' => 'photos/photo.png',
            'candidate_civil_photo_front' => 'photos/front.png',
            'candidate_civil_photo_back' => 'photos/back.png',
            'currency_code' => 'KWD',
            'university_id' => 1,
            'country_id' => 1,
            'candidate_video' => $video,
            'candidate_video_job_id' => $jobId,
            'candidate_video_processed' => 0,
            'candidate_job_search_status' => 0,
            'is_incomplete_profile' => 1,
            'candidate_pending_profile' => '',
            'deleted' => 0,
            'candidate_created_at' => '2026-01-01 00:00:00',
            'candidate_updated_at' => '2026-01-01 00:00:00',
        ])->execute();
    }

    /**
     * @return array<string, mixed>
     */
    private function row()
    {
        return \Yii::$app->db->createCommand('SELECT * FROM candidate WHERE candidate_id=17')->queryOne();
    }

    /**
     * @param object $detail
     */
    private function postDetail($detail)
    {
        \Yii::$app->request->setBodyParams([
            'detail' => $detail,
        ]);
    }

    /**
     * @return object
     */
    private function detail($jobId, $status, $errorMessage = null)
    {
        $detail = new \stdClass();
        $detail->jobId = $jobId;
        $detail->status = $status;
        if ($errorMessage !== null) {
            $detail->errorMessage = $errorMessage;
        }
        return $detail;
    }

    /**
     * @return object
     */
    private function completeDetail($jobId, $outputPath)
    {
        $paths = new \stdClass();
        $paths->outputFilePaths = [$outputPath];
        $group = new \stdClass();
        $group->outputDetails = [$paths];
        $detail = $this->detail($jobId, 'COMPLETE');
        $detail->outputGroupDetails = [$group];
        return $detail;
    }
}

class IdentityMessageSource extends MessageSource
{
    protected function loadMessages($category, $language)
    {
        return [];
    }
}

class CandidateVideoEventStub extends Component
{
    public function track()
    {
    }
}

class CandidateVideoStorageStub extends Component
{
    /** @var bool */
    public $fail = false;

    /** @var string[] */
    public $deleted = [];

    public function delete($path)
    {
        $this->deleted[] = $path;
        if ($this->fail) {
            throw new \RuntimeException('storage unavailable');
        }
    }

    public function getUrl($path)
    {
        return 'https://example.test/' . $path;
    }
}

class CandidateVideoTestIdentity extends Component implements IdentityInterface
{
    /** @var int */
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
        parent::__construct();
    }

    public static function findIdentity($id)
    {
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return 'test';
    }

    public function validateAuthKey($authKey)
    {
        return true;
    }
}
