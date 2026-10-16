<?php

namespace common\tests\unit\components;

use common\components\CandidateStaffNotificationService;
use common\models\Candidate;
use common\models\CandidateNotification;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;

/**
 * Disposable-database coverage for staff Activity notifications.
 * Skips unless UPDATE_REQUEST_TEST_DSN points at a non-production MySQL database.
 */
class CandidateStaffNotificationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $dsn = getenv('UPDATE_REQUEST_TEST_DSN');
        if (!$dsn) {
            return;
        }

        $root = dirname(__DIR__, 4);
        $vendor = $root . '/vendor/autoload.php';
        if (!is_file($vendor)) {
            throw new \RuntimeException('Composer autoload was not found at ' . $vendor . '. Run composer install in this repository.');
        }
        if (!defined('YII_DEBUG')) {
            define('YII_DEBUG', true);
        }
        if (!defined('YII_ENV')) {
            define('YII_ENV', 'test');
        }
        require_once $vendor;
        require_once dirname($vendor) . '/yiisoft/yii2/Yii.php';

        new Application([
            'id' => 'staff-notification-test',
            'basePath' => $root . '/common',
            'aliases' => ['@common' => $root . '/common'],
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => $dsn,
                    'username' => getenv('UPDATE_REQUEST_TEST_USER') ?: 'root',
                    'password' => getenv('UPDATE_REQUEST_TEST_PASSWORD') ?: 'root',
                    'charset' => 'utf8mb4',
                ],
            ],
        ]);

        self::installSchema();
    }

    protected function setUp(): void
    {
        if (!getenv('UPDATE_REQUEST_TEST_DSN') && $this->getName() !== 'testFeatureDoesNotTouchDocumentsOrExternalMessaging') {
            $this->markTestSkipped('Disposable database DSN was not provided.');
        }
        if (!getenv('UPDATE_REQUEST_TEST_DSN')) {
            return;
        }
        Yii::$app->db->createCommand()->delete('candidate_notification')->execute();
    }

    public function testFeatureDoesNotTouchDocumentsOrExternalMessaging()
    {
        $root = dirname(__DIR__, 4);
        $service = file_get_contents($root . '/common/components/CandidateStaffNotificationService.php');
        $this->assertStringNotContainsString('MobileNotification', $service);
        $this->assertStringNotContainsString('MailLog', $service);
        $this->assertStringNotContainsString('SMSComponent', $service);
        $this->assertStringNotContainsString('stageCivil', $service);
        $this->assertFileDoesNotExist($root . '/console/migrations/m261015_140000_candidate_update_requests.php');

        foreach ([
            'candidate/modules/v1/controllers/AccountController.php',
            'candidate/modules/v1/controllers/TempUploadController.php',
            'common/models/Candidate.php',
        ] as $relative) {
            $source = file_get_contents($root . '/' . $relative);
            $this->assertStringNotContainsString('CandidateStaffNotificationService', $source, $relative);
            $this->assertStringNotContainsString('stageCivilSide', $source, $relative);
            $this->assertStringNotContainsString('recordEligibleUpload', $source, $relative);
            $this->assertStringNotContainsString('commitRequestedCivilId', $source, $relative);
        }
    }

    public function testDocumentRequestsAreUnreadActivityRowsAndDoNotChangeTheProfile()
    {
        $candidate = Candidate::findOne(1);
        $photoKey = '11111111-1111-4111-8111-111111111111';
        $civilKey = '22222222-2222-4222-8222-222222222222';

        $photo = CandidateStaffNotificationService::sendDocumentRequest($candidate, 1, 'profile_photo', $photoKey);
        $civil = CandidateStaffNotificationService::sendDocumentRequest($candidate, 1, 'civil_id', $civilKey);
        $candidate->refresh();

        $this->assertSame('success', $photo['operation'], $photo['message'] ?? '');
        $this->assertFalse($photo['already_sent']);
        $this->assertSame(CandidateNotification::TYPE_PROFILE_PHOTO_REQUEST, (int) $photo['notification']->type);
        $this->assertSame(1, (int) $photo['notification']->is_new);
        $this->assertSame('Please upload a new profile photo.', $photo['notification']->message);
        $this->assertSame(CandidateNotification::TYPE_CIVIL_ID_REQUEST, (int) $civil['notification']->type);
        $this->assertSame('old-photo.jpg', $candidate->candidate_personal_photo);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame(2, (int) CandidateNotification::find()->count());
    }

    public function testTheSameSendKeyDoesNotInsertAnotherRow()
    {
        $candidate = Candidate::findOne(1);
        $key = '33333333-3333-4333-8333-333333333333';
        CandidateStaffNotificationService::sendDocumentRequest($candidate, 1, 'profile_photo', strtoupper($key));

        $again = CandidateStaffNotificationService::sendDocumentRequest($candidate, 1, 'profile_photo', $key);
        $otherCandidate = CandidateStaffNotificationService::sendDocumentRequest(Candidate::findOne(2), 1, 'profile_photo', $key);
        $otherType = CandidateStaffNotificationService::sendCustomNotification(Candidate::findOne(1), 1, 'Hello', $key);

        $this->assertTrue($again['already_sent']);
        $this->assertSame($key, $again['notification']->cn_uuid);
        $this->assertSame('error', $otherCandidate['operation']);
        $this->assertSame('error', $otherType['operation']);
        $this->assertSame(1, (int) CandidateNotification::find()->count());
    }

    public function testALaterReminderUsesANewKeyAndReadingKeepsTheRow()
    {
        $candidate = Candidate::findOne(1);
        $first = CandidateStaffNotificationService::sendDocumentRequest($candidate, 1, 'civil_id', '44444444-4444-4444-8444-444444444444');
        $second = CandidateStaffNotificationService::sendDocumentRequest($candidate, 1, 'civil_id', '55555555-5555-4555-8555-555555555555');
        $first['notification']->is_new = false;
        $this->assertTrue($first['notification']->save(false));

        $saved = CandidateNotification::findOne($first['notification']->cn_uuid);
        $this->assertSame(0, (int) $saved->is_new);
        $this->assertSame(2, (int) CandidateNotification::find()->where(['type' => CandidateNotification::TYPE_CIVIL_ID_REQUEST])->count());
        $this->assertNotSame($first['notification']->cn_uuid, $second['notification']->cn_uuid);
    }

    public function testCustomMessageStaysPlainTextAndRejectsAnEmptyOrLongMessage()
    {
        $candidate = Candidate::findOne(1);
        $text = "Please update your IBAN.\n<b>Not markup</b>";
        $sent = CandidateStaffNotificationService::sendCustomNotification(
            $candidate,
            1,
            '  ' . $text . '  ',
            '66666666-6666-4666-8666-666666666666'
        );
        $blank = CandidateStaffNotificationService::sendCustomNotification($candidate, 1, '   ', '77777777-7777-4777-8777-777777777777');
        $long = CandidateStaffNotificationService::sendCustomNotification($candidate, 1, str_repeat('a', 1001), '88888888-8888-4888-8888-888888888888');
        $duplicate = CandidateStaffNotificationService::sendCustomNotification($candidate, 1, 'different', '66666666-6666-4666-8666-666666666666');

        $this->assertSame(CandidateNotification::TYPE_STAFF_MESSAGE, (int) $sent['notification']->type);
        $this->assertSame($text, $sent['notification']->message);
        $this->assertSame('error', $blank['operation']);
        $this->assertSame('error', $long['operation']);
        $this->assertTrue($duplicate['already_sent']);
        $this->assertSame($text, $duplicate['notification']->message);
        $this->assertSame(1, (int) CandidateNotification::find()->count());
    }

    private static function installSchema()
    {
        $db = Yii::$app->db;
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ([
            'candidate_update_request_upload',
            'candidate_update_request',
            'candidate_notification',
            'candidate',
            'staff',
        ] as $table) {
            $db->createCommand('DROP TABLE IF EXISTS ' . $db->quoteTableName($table))->execute();
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();

        $db->createCommand('CREATE TABLE staff (
            staff_id INT NOT NULL PRIMARY KEY,
            staff_name VARCHAR(255) NULL
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE candidate (
            candidate_id INT NOT NULL PRIMARY KEY,
            candidate_personal_photo VARCHAR(255) NULL,
            candidate_civil_id VARCHAR(255) NULL,
            candidate_civil_expiry_date DATE NULL,
            candidate_civil_photo_front VARCHAR(255) NULL,
            candidate_civil_photo_back VARCHAR(255) NULL,
            deleted TINYINT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE candidate_notification (
            cn_uuid CHAR(60) NOT NULL PRIMARY KEY,
            candidate_id INT NOT NULL,
            type TINYINT NOT NULL,
            candidate_work_history_id INT NULL,
            candidate_working_date_uuid CHAR(60) NULL,
            candidate_working_hour_uuid CHAR(60) NULL,
            invitation_uuid CHAR(60) NULL,
            request_uuid CHAR(60) NULL,
            appeal_uuid CHAR(60) NULL,
            tc_id INT NULL,
            cwlf_uuid CHAR(60) NULL,
            company_id INT NULL,
            store_id INT NULL,
            staff_id INT NULL,
            job_uuid CHAR(60) NULL,
            job_interest_uuid CHAR(60) NULL,
            is_new TINYINT NULL DEFAULT 1,
            message TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB')->execute();
        $db->createCommand()->insert('staff', ['staff_id' => 1, 'staff_name' => 'Test Staff'])->execute();
        $db->createCommand()->insert('candidate', [
            'candidate_id' => 1,
            'candidate_personal_photo' => 'old-photo.jpg',
            'candidate_civil_id' => '200000000001',
            'candidate_civil_expiry_date' => '2030-01-01',
            'candidate_civil_photo_front' => 'old-front.jpg',
            'candidate_civil_photo_back' => 'old-back.jpg',
        ])->execute();
        $db->createCommand()->insert('candidate', [
            'candidate_id' => 2,
            'candidate_civil_id' => '200000000002',
        ])->execute();
    }
}
