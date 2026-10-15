<?php

namespace common\tests\unit\components;

use common\components\CandidateUpdateRequestService;
use common\models\Candidate;
use common\models\CandidateNotification;
use common\models\CandidateUpdateRequest;
use common\models\CandidateUpdateRequestUpload;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;

/**
 * Disposable-database coverage for photo and Civil ID update requests.
 * Skips unless UPDATE_REQUEST_TEST_DSN points at a non-production MySQL database.
 */
class CandidateUpdateRequestFlowTest extends TestCase
{
    /** @var object */
    private static $storage;

    public static function setUpBeforeClass(): void
    {
        $dsn = getenv('UPDATE_REQUEST_TEST_DSN');
        if (!$dsn) {
            return;
        }

        $root = dirname(__DIR__, 4);
        $vendor = self::repositoryAutoload($root);
        if (!defined('YII_DEBUG')) {
            define('YII_DEBUG', true);
        }
        if (!defined('YII_ENV')) {
            define('YII_ENV', 'test');
        }
        require_once $vendor;
        require_once dirname($vendor) . '/yiisoft/yii2/Yii.php';
        require_once $root . '/console/migrations/m261015_140000_candidate_update_requests.php';

        new Application([
            'id' => 'update-request-test',
            'basePath' => $root . '/common',
            'aliases' => [
                '@common' => $root . '/common',
            ],
            'components' => [
                'i18n' => [
                    'translations' => [
                        'candidate*' => [
                            'class' => 'yii\i18n\PhpMessageSource',
                            'forceTranslation' => false,
                        ],
                        'app*' => [
                            'class' => 'yii\i18n\PhpMessageSource',
                            'forceTranslation' => false,
                        ],
                    ],
                ],
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
        self::$storage = new class extends \yii\base\Component {
            public $bucket = 'permanent-test';
            public $copied = [];
            public $deleted = [];
            public $failDeleteContaining = null;
            public function copy($oldFile, $newFile, $sourceBucket = '', $options = [])
            {
                $destination = (string) $newFile;
                if (strpos($destination, 'fail-back') !== false) {
                    throw new \RuntimeException('copy failed');
                }
                if (strpos($destination, 'fail-commit') !== false && strpos($destination, '/staging/') === false) {
                    throw new \RuntimeException('permanent copy failed');
                }
                $this->copied[] = [
                    'oldFile' => $oldFile,
                    'newFile' => $newFile,
                    'sourceBucket' => $sourceBucket,
                ];
            }
            public function delete($name)
            {
                if ($this->failDeleteContaining && strpos((string) $name, (string) $this->failDeleteContaining) !== false) {
                    throw new \RuntimeException('delete failed');
                }
                $this->deleted[] = $name;
            }
        };
        $temp = new class extends \yii\base\Component {
            public $bucket = 'temp-test';
        };
        Yii::$app->set('resourceManager', self::$storage);
        Yii::$app->set('temporaryBucketResourceManager', $temp);
    }

    protected function setUp(): void
    {
        if (!getenv('UPDATE_REQUEST_TEST_DSN') && $this->getName() !== 'testServiceDoesNotSendMailOrPush') {
            $this->markTestSkipped('Disposable database DSN was not provided.');
        }
        if (!getenv('UPDATE_REQUEST_TEST_DSN')) {
            return;
        }
        if (Yii::$app->has('idExpiryDateExtractor')) {
            Yii::$app->clear('idExpiryDateExtractor');
        }
        self::$storage->copied = [];
        self::$storage->deleted = [];
        self::$storage->failDeleteContaining = null;
        CandidateUpdateRequestService::$stagingCleanupBeforeDelete = null;
        CandidateUpdateRequestService::$failStagingReferenceLookup = false;
        Yii::$app->db->createCommand()->delete('candidate_notification')->execute();
        Yii::$app->db->createCommand()->delete('candidate_update_request_upload')->execute();
        Yii::$app->db->createCommand()->delete('candidate_update_request')->execute();
        Yii::$app->db->createCommand()->update('candidate', [
            'candidate_personal_photo' => 'old-photo.jpg',
            'candidate_civil_id' => '200000000001',
            'candidate_civil_expiry_date' => '2030-01-01',
            'candidate_civil_photo_front' => 'old-front.jpg',
            'candidate_civil_photo_back' => 'old-back.jpg',
            'candidate_civil_need_verification' => 0,
        ], ['candidate_id' => 1])->execute();
    }

    public function testServiceDoesNotSendMailOrPush()
    {
        $source = file_get_contents(dirname(__DIR__, 4) . '/common/components/CandidateUpdateRequestService.php');
        $this->assertStringNotContainsString('MobileNotification', $source);
        $this->assertStringNotContainsString('MailLog', $source);
    }

    public function testCustomNotificationIsOneActivityRowAndLeavesDocumentRequestsAlone()
    {
        $candidate = Candidate::findOne(1);
        $photo = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        $civil = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $message = 'Please update your IBAN; the current details are incorrect.';
        $key = '11111111-1111-4111-8111-111111111111';

        $empty = CandidateUpdateRequestService::sendCustomNotification($candidate, 1, '   ', $key);
        $tooLong = CandidateUpdateRequestService::sendCustomNotification($candidate, 1, str_repeat('a', 1001), $key);
        $first = CandidateUpdateRequestService::sendCustomNotification($candidate, 1, $message, $key);
        $second = CandidateUpdateRequestService::sendCustomNotification($candidate, 1, $message, $key);
        $otherCandidate = CandidateUpdateRequestService::sendCustomNotification(Candidate::findOne(2), 1, 'Other message', $key);

        $this->assertSame('error', $empty['operation']);
        $this->assertSame('error', $tooLong['operation']);
        $this->assertSame('success', $first['operation']);
        $this->assertFalse($first['already_sent']);
        $this->assertTrue($second['already_sent']);
        $this->assertSame('error', $otherCandidate['operation']);
        $this->assertSame(1, (int) CandidateNotification::find()->where(['type' => CandidateNotification::TYPE_STAFF_MESSAGE])->count());

        $saved = CandidateNotification::findOne(['cn_uuid' => $key]);
        $this->assertSame(1, (int) $saved->candidate_id);
        $this->assertSame($message, $saved->message);
        $this->assertSame(1, (int) $saved->is_new);
        $this->assertNull($saved->update_request_uuid);

        $saved->is_new = false;
        $this->assertTrue($saved->save(false));
        $saved->refresh();
        $this->assertSame(0, (int) $saved->is_new);
        $this->assertSame($message, $saved->message);

        $photo['request']->refresh();
        $civil['request']->refresh();
        $this->assertSame(CandidateUpdateRequest::STATUS_PENDING, $photo['request']->status);
        $this->assertSame(CandidateUpdateRequest::STATUS_PENDING, $civil['request']->status);
        $this->assertSame([], CandidateUpdateRequestService::pendingForCandidate(2));
    }

    public function testDuplicateRequestDoesNotCreateAnotherNotification()
    {
        $candidate = Candidate::findOne(1);
        $first = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        $second = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);

        $this->assertFalse($first['already_pending']);
        $this->assertTrue($second['already_pending']);
        $this->assertSame($first['request']->cur_uuid, $second['request']->cur_uuid);
        $this->assertSame(1, (int) CandidateNotification::find()->count());
    }

    public function testMarkReadLeavesTheRequestPending()
    {
        $candidate = Candidate::findOne(1);
        $created = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        $notification = CandidateNotification::findOne(['update_request_uuid' => $created['request']->cur_uuid]);
        $notification->is_new = false;
        $this->assertTrue($notification->save(false));

        $pending = CandidateUpdateRequestService::pendingForCandidate(1);
        $this->assertCount(1, $pending);
        $this->assertSame(CandidateUpdateRequest::STATUS_PENDING, $created['request']->status);
        $this->assertSame('pending', $notification->toArray()['update_request_status']);
    }

    public function testPhotoCompletionIsIndependentOfCivilRequest()
    {
        $candidate = Candidate::findOne(1);
        CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        CandidateUpdateRequestService::recordEligibleUpload(1, 'profile_photo', 'temp-photo.jpg');

        CandidateUpdateRequestService::completeProfilePhoto($candidate, 'old-photo.jpg', 'old-photo.jpg', 'temp-photo.jpg');
        $this->assertSame(
            CandidateUpdateRequest::STATUS_PENDING,
            CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO)->status
        );

        CandidateUpdateRequestService::completeProfilePhoto($candidate, 'old-photo.jpg', 'candidate-profile-photos/temp-photo.jpg', 'stale-photo.jpg');
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO));

        CandidateUpdateRequestService::completeProfilePhoto($candidate, 'old-photo.jpg', 'candidate-profile-photos/temp-photo.jpg', 'temp-photo.jpg');
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO));
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
    }

    public function testFrontStageDoesNotChangeCanonicalCivilId()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');

        $response = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');
        $candidate->refresh();

        $this->assertSame('success', $response['operation']);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('2030-01-01', (string) $candidate->candidate_civil_expiry_date);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertSame(0, (int) $candidate->candidate_civil_need_verification);
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key);
    }

    public function testFailedBackCopyLeavesCanonicalCivilId()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'fail-back.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');

        $response = CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'fail-back.jpg');
        $candidate->refresh();
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);

        $this->assertSame('error', $response['operation']);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertNull($request->staged_back_key);
        $this->assertNotNull($request->staged_front_key);
    }

    public function testStagedRemovalReportsTheNewRevisionAndLeavesTheSavedPhoto()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        $staged = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');

        $removed = CandidateUpdateRequestService::clearStagedSide($candidate, 'front');
        $candidate->refresh();
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);

        $this->assertSame('success', $removed['operation']);
        $this->assertSame('Civil photo front removed.', $removed['message']);
        $this->assertSame($staged['update_request_id'], $removed['update_request_id']);
        $this->assertSame((int) $staged['staging_revision'] + 1, (int) $removed['staging_revision']);
        $this->assertNull($request->staged_front_key);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertNull(CandidateUpdateRequestService::clearStagedSide(Candidate::findOne(2), 'front'));
    }

    public function testStaleStagedRemovalDoesNotDeleteTheSavedPhoto()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $script = tempnam(sys_get_temp_dir(), 'cur') . '.php';
        file_put_contents($script, $this->overlapWorkerSource());
        $hold = proc_open(
            [PHP_BINARY, $script, 'hold-submit', $request->cur_uuid],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $holdPipes
        );
        $this->assertIsResource($hold);
        stream_set_blocking($holdPipes[1], false);
        $held = '';
        $deadline = time() + 20;
        while (time() < $deadline && strpos($held, 'HOLDING') === false) {
            $held .= (string) stream_get_contents($holdPipes[1]);
            usleep(50000);
        }
        $this->assertStringContainsString('HOLDING', $held);

        $removed = CandidateUpdateRequestService::clearStagedSide($candidate, 'front');
        $holdError = stream_get_contents($holdPipes[2]);
        $holdCode = proc_close($hold);
        @unlink($script);
        $candidate->refresh();
        $request->refresh();

        $this->assertSame(0, $holdCode, $holdError);
        $this->assertSame('error', $removed['operation']);
        $this->assertSame('This Civil ID update is out of date. Refresh the page and submit it again.', $removed['message']);
        $this->assertArrayNotHasKey('staging_revision', $removed);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertNotNull($request->staged_front_key);
        $this->assertSame(CandidateUpdateRequest::STATUS_SUBMITTED, $request->status);
    }

    public function testPastBackDateDoesNotOverrideAValidFrontExpiry()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'future-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'past-back.jpg');
        Yii::$app->set('idExpiryDateExtractor', new class extends \yii\base\Component {
            public function extractExpiryDate($key)
            {
                $key = (string) $key;
                if (strpos($key, 'past-back') !== false) {
                    return ['operation' => 'success', 'matches' => ['01/01/1990'], 'ids' => ['111111111111']];
                }
                if (strpos($key, 'future-front') !== false) {
                    return ['operation' => 'success', 'matches' => ['01/01/2031'], 'ids' => ['200000000009']];
                }
                if (strpos($key, 'plain-front') !== false) {
                    return ['operation' => 'success', 'matches' => [], 'ids' => []];
                }

                return ['operation' => 'error'];
            }
        });

        $front = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'future-front.jpg');
        $back = CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'past-back.jpg');
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);

        $this->assertSame('success', $front['operation']);
        $this->assertSame('2031-01-01', $front['suggested_civil_expiry_date']);
        $this->assertSame('success', $back['operation']);
        $this->assertArrayNotHasKey('suggested_civil_expiry_date', $back);
        $this->assertNotNull($request->staged_back_key);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);

        $onlyBack = Candidate::findOne(2);
        CandidateUpdateRequestService::create($onlyBack, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        CandidateUpdateRequestService::recordEligibleUpload(2, 'civil_id', 'past-back.jpg');
        $rejected = CandidateUpdateRequestService::stageCivilSide($onlyBack, 'back', 'past-back.jpg');
        $this->assertSame('error', $rejected['operation']);
        $this->assertSame('Invalid Civil ID (Expired)', $rejected['message']);

        CandidateUpdateRequestService::recordEligibleUpload(2, 'civil_id', 'plain-front.jpg');
        $plain = CandidateUpdateRequestService::stageCivilSide($onlyBack, 'front', 'plain-front.jpg');
        $this->assertSame('success', $plain['operation']);
        $stillRejected = CandidateUpdateRequestService::stageCivilSide($onlyBack, 'back', 'past-back.jpg');
        $this->assertSame('error', $stillRejected['operation']);
        $this->assertSame('Invalid Civil ID (Expired)', $stillRejected['message']);
    }

    public function testCommitDeletesStagingCopiesAndIgnoresACleanupFailure()
    {
        $candidate = $this->stageBothSides();
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $frontKey = $request->staged_front_key;
        $backKey = $request->staged_back_key;

        $response = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('success', $response['operation']);
        $this->assertSame('new-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('new-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertContains($frontKey, self::$storage->deleted);
        $this->assertContains($backKey, self::$storage->deleted);
        $this->assertNotContains(
            Candidate::normalizeCivilIdPermanentS3Key('new-front.jpg'),
            self::$storage->deleted
        );
        $this->assertNotContains(
            Candidate::normalizeCivilIdPermanentS3Key('new-back.jpg'),
            self::$storage->deleted
        );

        $again = Candidate::findOne(1);
        CandidateUpdateRequestService::create($again, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'other-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'other-back.jpg');
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($again, 'front', 'other-front.jpg')['operation']);
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($again, 'back', 'other-back.jpg')['operation']);
        $staged = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        self::$storage->deleted = [];
        self::$storage->failDeleteContaining = 'photos/staging/';
        $failedCleanup = $this->commitCurrent($again, '200000000010', '2032-02-02');
        $again->refresh();

        $this->assertSame('success', $failedCleanup['operation']);
        $this->assertSame('200000000010', (string) $again->candidate_civil_id);
        $this->assertSame('other-front.jpg', $again->candidate_civil_photo_front);
        $this->assertSame('other-back.jpg', $again->candidate_civil_photo_back);
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
        $this->assertNotContains($staged->staged_front_key, self::$storage->deleted);
        $this->assertNotContains($staged->staged_back_key, self::$storage->deleted);
    }

    public function testRejectedAndReplacedStagingCopiesKeepAKeyThatIsStillPending()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'active-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'orphan-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'replacement-front.jpg');
        $extractor = new class extends \yii\base\Component {
            public $expire = false;
            public function extractExpiryDate($key)
            {
                $key = (string) $key;
                if ($this->expire || strpos($key, 'orphan-front') !== false) {
                    return ['operation' => 'success', 'matches' => ['01/01/1990'], 'ids' => []];
                }

                return ['operation' => 'success', 'matches' => ['01/01/2031'], 'ids' => ['200000000009']];
            }
        };
        Yii::$app->set('idExpiryDateExtractor', $extractor);

        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'active-front.jpg')['operation']);
        $activeKey = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key;
        $extractor->expire = true;
        $retried = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'active-front.jpg');
        $this->assertSame('error', $retried['operation']);
        $this->assertNotContains($activeKey, self::$storage->deleted);
        $this->assertSame($activeKey, CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key);

        $rejected = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'orphan-front.jpg');
        $orphanDeletes = array_values(array_filter(self::$storage->deleted, function ($name) {
            return is_string($name) && substr($name, -strlen('/orphan-front.jpg')) === '/orphan-front.jpg';
        }));
        $this->assertSame('error', $rejected['operation']);
        $this->assertCount(1, $orphanDeletes);
        $this->assertNotSame($activeKey, $orphanDeletes[0]);
        $this->assertSame($activeKey, CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key);

        $extractor->expire = false;
        $replaced = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'replacement-front.jpg');
        $replacementKey = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key;
        $this->assertSame('success', $replaced['operation']);
        $this->assertNotSame($activeKey, $replacementKey);
        $this->assertContains($activeKey, self::$storage->deleted);
        $this->assertNotContains($replacementKey, self::$storage->deleted);

        $cleared = CandidateUpdateRequestService::clearStagedSide($candidate, 'front');
        $this->assertSame('success', $cleared['operation']);
        $this->assertContains($replacementKey, self::$storage->deleted);
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key);
    }

    public function testPausedCleanupDoesNotDeleteARetryOfTheSameFile()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'same-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'same-back.jpg');
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'same-back.jpg')['operation']);
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'same-front.jpg')['operation']);
        $firstFront = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->staged_front_key;

        $accepted = null;
        CandidateUpdateRequestService::$stagingCleanupBeforeDelete = function () use ($candidate, &$accepted) {
            CandidateUpdateRequestService::$stagingCleanupBeforeDelete = null;
            $accepted = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'same-front.jpg');
        };

        $duringCleanup = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'same-front.jpg');
        $pending = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);

        $this->assertSame('success', $duringCleanup['operation']);
        $this->assertSame('success', $accepted['operation']);
        $this->assertNotSame($firstFront, $pending->staged_front_key);
        $this->assertSame('same-front.jpg', basename($pending->staged_front_key));
        $this->assertContains($firstFront, self::$storage->deleted);
        $this->assertNotContains($pending->staged_front_key, self::$storage->deleted);
        $this->assertContains($pending->staged_front_key, array_column(self::$storage->copied, 'newFile'));

        $committed = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('success', $committed['operation']);
        $this->assertSame('same-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('same-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
    }

    public function testStagingReferenceLookupFailureLeavesTheCommitSuccessful()
    {
        $candidate = $this->stageBothSides();
        CandidateUpdateRequestService::$failStagingReferenceLookup = true;

        $response = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('success', $response['operation']);
        $this->assertSame('200000000009', (string) $candidate->candidate_civil_id);
        $this->assertSame('new-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('new-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
    }

    public function testOverlappingStageDoesNotDeleteTheActiveStagingKey()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'active-front.jpg');
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'active-front.jpg')['operation']);
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $activeKey = $request->staged_front_key;
        $script = tempnam(sys_get_temp_dir(), 'cur') . '.php';
        file_put_contents($script, $this->overlapWorkerSource());
        $hold = proc_open(
            [PHP_BINARY, $script, 'hold-replace', $request->cur_uuid],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $holdPipes
        );
        $this->assertIsResource($hold);
        stream_set_blocking($holdPipes[1], false);
        $held = '';
        $deadline = time() + 20;
        while (time() < $deadline && strpos($held, 'HOLDING') === false) {
            $held .= (string) stream_get_contents($holdPipes[1]);
            usleep(50000);
        }
        $this->assertStringContainsString('HOLDING', $held);

        Yii::$app->set('idExpiryDateExtractor', new class extends \yii\base\Component {
            public function extractExpiryDate($key)
            {
                return ['operation' => 'success', 'matches' => ['01/01/1990'], 'ids' => []];
            }
        });
        $retried = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'active-front.jpg');
        $holdError = stream_get_contents($holdPipes[2]);
        $holdCode = proc_close($hold);
        @unlink($script);
        $request->refresh();

        $this->assertSame(0, $holdCode, $holdError);
        $this->assertSame('error', $retried['operation']);
        $this->assertNotContains($activeKey, self::$storage->deleted);
        $this->assertSame($activeKey, $request->staged_front_key);
    }

    public function testAbandonedFormAndInvalidExpiryDoNotCommit()
    {
        $candidate = $this->stageBothSides();
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);

        $copiesBefore = count(self::$storage->copied);
        $response = $this->commitCurrent($candidate, '200000000009', '2020-01-01');
        $candidate->refresh();

        $this->assertSame('error', $response['operation']);
        $this->assertSame($copiesBefore, count(self::$storage->copied));
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('2030-01-01', (string) $candidate->candidate_civil_expiry_date);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
        $this->assertSame([], self::$storage->deleted);
    }

    public function testCommitWritesCanonicalFieldsTogetherAndAllowsANewRequest()
    {
        $candidate = $this->stageBothSides();
        $response = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('success', $response['operation']);
        $this->assertSame('200000000009', (string) $candidate->candidate_civil_id);
        $this->assertSame('2031-06-01', (string) $candidate->candidate_civil_expiry_date);
        $this->assertSame('new-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('new-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertSame(1, (int) $candidate->candidate_civil_need_verification);
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
        $this->assertNotEmpty(self::$storage->deleted);

        $again = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $this->assertFalse($again['already_pending']);
        $this->assertSame(2, (int) CandidateNotification::find()->where(['type' => CandidateNotification::TYPE_CIVIL_ID_REQUEST])->count());
    }

    public function testStaleSubmissionDoesNotCompleteANewerRequest()
    {
        $candidate = $this->stageBothSides();
        $completed = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $oldId = $completed->cur_uuid;
        $oldRevision = (int) $completed->staging_revision;
        $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();
        $newer = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'newer-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'newer-back.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'newer-front.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'newer-back.jpg');

        $replay = CandidateUpdateRequestService::commitCivil($candidate, '200000000010', '2032-01-01', $oldId, $oldRevision);
        $candidate->refresh();

        $this->assertSame('error', $replay['operation']);
        $this->assertSame('200000000009', (string) $candidate->candidate_civil_id);
        $this->assertSame('new-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame($newer['request']->cur_uuid, CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->cur_uuid);

        $staleStage = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');
        $this->assertSame('error', $staleStage['operation']);
    }

    public function testReplacedStagingRejectsThePreviousRevision()
    {
        $candidate = $this->stageBothSides();
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $requestId = $request->cur_uuid;
        $oldRevision = (int) $request->staging_revision;

        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'replaced-back.jpg');
        $this->assertSame(
            'success',
            CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'replaced-back.jpg')['operation']
        );

        $stale = CandidateUpdateRequestService::commitCivil(
            $candidate,
            '200000000009',
            '2031-06-01',
            $requestId,
            $oldRevision
        );
        $candidate->refresh();

        $this->assertSame('error', $stale['operation']);
        $this->assertStringContainsString('out of date', $stale['message']);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);
        $current = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $this->assertSame($requestId, $current->cur_uuid);
        $this->assertGreaterThan($oldRevision, (int) $current->staging_revision);

        $currentCommit = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();
        $this->assertSame('success', $currentCommit['operation']);
        $this->assertSame('replaced-back.jpg', $candidate->candidate_civil_photo_back);
    }

    public function testRepeatedSubmissionDoesNotOverwriteTheSavedCivilId()
    {
        $candidate = $this->stageBothSides();
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $first = CandidateUpdateRequestService::commitCivil(
            $candidate,
            '200000000009',
            '2031-06-01',
            $request->cur_uuid,
            (int) $request->staging_revision
        );
        $this->assertSame('success', $first['operation']);

        $second = CandidateUpdateRequestService::commitCivil(
            $candidate,
            '200000000010',
            '2032-01-01',
            $request->cur_uuid,
            (int) $request->staging_revision
        );
        $candidate->refresh();

        $this->assertSame('error', $second['operation']);
        $this->assertSame('200000000009', (string) $candidate->candidate_civil_id);
        $this->assertSame('2031-06-01', (string) $candidate->candidate_civil_expiry_date);
        $this->assertSame('new-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
    }

    public function testOlderClientWithoutARequestTokenFailsWhileARequestIsPending()
    {
        $candidate = $this->stageBothSides();
        $response = CandidateUpdateRequestService::commitCivil($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('error', $response['operation']);
        $this->assertStringContainsString('out of date', $response['message']);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
    }

    public function testMissingCivilFieldsUpdateStoredCompletionAfterCommit()
    {
        Yii::$app->db->createCommand()->insert('country', [
            'country_id' => 1,
            'country_nationality_name_en' => 'Test',
        ])->execute();
        Yii::$app->db->createCommand()->insert('candidate_education', ['candidate_id' => 1])->execute();
        Yii::$app->db->createCommand()->insert('candidate_skill', ['candidate_id' => 1])->execute();
        Yii::$app->db->createCommand()->update('candidate', [
            'candidate_uid' => 'uidcomplete01',
            'candidate_name' => 'Synthetic Name',
            'candidate_name_ar' => 'Synthetic Arabic',
            'candidate_email' => 'candidate1@example.test',
            'candidate_phone' => '50000001',
            'candidate_gender' => Candidate::GENDER_MALE,
            'candidate_objective' => 'Work',
            'candidate_birth_date' => '2000-01-01',
            'candidate_driving_license' => 1,
            'candidate_latitude' => '29.3',
            'candidate_longitude' => '47.9',
            'country_id' => 1,
            'currency_code' => 'KWD',
            'candidate_personal_photo' => 'old-photo.jpg',
            'candidate_civil_id' => null,
            'candidate_civil_expiry_date' => null,
            'candidate_civil_photo_front' => null,
            'candidate_civil_photo_back' => null,
            'is_incomplete_profile' => 1,
            'candidate_pending_profile' => 'civil id,civil expiry date,civil photo front,civil photo back',
        ], ['candidate_id' => 1])->execute();

        $before = Candidate::findOne(1);
        $before->pendingProfile = [];
        $this->assertTrue($before->isInCompleteProfile());
        $this->assertArrayHasKey('civil id', $before->pendingProfile);
        $this->assertArrayHasKey('civil photo front', $before->pendingProfile);

        $candidate = $this->stageBothSides();
        $response = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('success', $response['operation']);
        $this->assertSame(0, (int) $candidate->is_incomplete_profile);
        $this->assertSame(0, (int) $response['is_incomplete_profile']);
        $this->assertStringNotContainsString('civil id', (string) $candidate->candidate_pending_profile);
        $this->assertStringNotContainsString('civil expiry date', (string) $candidate->candidate_pending_profile);
        $this->assertStringNotContainsString('civil photo front', (string) $candidate->candidate_pending_profile);
        $this->assertStringNotContainsString('civil photo back', (string) $candidate->candidate_pending_profile);
        $this->assertSame('', (string) $candidate->candidate_pending_profile);
    }

    public function testFinalSubmitWithoutBothSidesDoesNotCommit()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');

        $response = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('error', $response['operation']);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
    }

    public function testAnotherCandidateCannotCompleteTheRequest()
    {
        $owner = Candidate::findOne(1);
        CandidateUpdateRequestService::create($owner, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        CandidateUpdateRequestService::recordEligibleUpload(1, 'profile_photo', 'temp-photo.jpg');

        $other = Candidate::findOne(2);
        $other->candidate_personal_photo = 'old-photo.jpg';
        CandidateUpdateRequestService::completeProfilePhoto(
            $other,
            'old-photo.jpg',
            'candidate-profile-photos/temp-photo.jpg',
            'temp-photo.jpg'
        );

        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO));
        $this->assertSame([], CandidateUpdateRequestService::pendingForCandidate(2));
    }

    public function testPermanentCopyFailureLeavesCanonicalCivilIdPending()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'fail-commit.jpg');
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg')['operation']);
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'fail-commit.jpg')['operation']);

        $response = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();

        $this->assertSame('error', $response['operation']);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
        $this->assertSame([], self::$storage->deleted);
    }

    public function testDatabaseSaveFailureRollsBackCanonicalCivilId()
    {
        $candidate = $this->stageBothSides();
        Yii::$app->db->createCommand('SET GLOBAL log_bin_trust_function_creators = 1')->execute();
        Yii::$app->db->createCommand('DROP TRIGGER IF EXISTS civil_commit_fail')->execute();
        Yii::$app->db->createCommand(
            "CREATE TRIGGER civil_commit_fail BEFORE UPDATE ON candidate
            FOR EACH ROW
            BEGIN
                IF NEW.candidate_civil_id = '200000000099' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forced failure';
                END IF;
            END"
        )->execute();

        try {
            $response = $this->commitCurrent($candidate, '200000000099', '2031-06-01');
            $candidate->refresh();

            $this->assertSame('error', $response['operation']);
            $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
            $this->assertSame('2030-01-01', (string) $candidate->candidate_civil_expiry_date);
            $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
            $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);
            $this->assertSame(0, (int) $candidate->candidate_civil_need_verification);
            $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));
            $this->assertSame([], self::$storage->deleted);
        } finally {
            Yii::$app->db->createCommand('DROP TRIGGER IF EXISTS civil_commit_fail')->execute();
        }
    }

    public function testExpiryRuleMatchesTheExistingCandidateValidator()
    {
        $candidate = Candidate::findOne(1);
        $candidate->candidate_civil_expiry_date = '2020-01-01';
        $this->assertFalse($candidate->validate(['candidate_civil_expiry_date']));

        $candidate->clearErrors();
        $candidate->candidate_civil_expiry_date = date('Y-m-d');
        $this->assertTrue($candidate->validate(['candidate_civil_expiry_date']));

        $staged = $this->stageBothSides();
        $response = $this->commitCurrent($staged, '200000000010', date('Y-m-d'));
        $staged->refresh();
        $this->assertSame('success', $response['operation']);
        $this->assertSame(date('Y-m-d'), (string) $staged->candidate_civil_expiry_date);
    }

    public function testEligibleUploadFailureIsRetryableAndLeavesUnrelatedUploadsAlone()
    {
        $candidate = Candidate::findOne(1);
        $this->assertTrue(CandidateUpdateRequestService::recordEligibleUpload(1, 'profile_photo', 'before-request.jpg'));
        $this->assertSame(0, (int) CandidateUpdateRequestUpload::find()->count());

        CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO);
        Yii::$app->db->createCommand('SET GLOBAL log_bin_trust_function_creators = 1')->execute();
        Yii::$app->db->createCommand('DROP TRIGGER IF EXISTS upload_track_fail')->execute();
        Yii::$app->db->createCommand(
            "CREATE TRIGGER upload_track_fail BEFORE INSERT ON candidate_update_request_upload
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forced failure';
            END"
        )->execute();

        try {
            $this->assertFalse(CandidateUpdateRequestService::recordEligibleUpload(1, 'profile_photo', 'new-photo.jpg'));
            $this->assertSame(0, (int) CandidateUpdateRequestUpload::find()->count());
            $this->assertTrue(CandidateUpdateRequestService::recordEligibleUpload(1, 'introduction_video', 'clip.mp4'));
            $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_PROFILE_PHOTO));
        } finally {
            Yii::$app->db->createCommand('DROP TRIGGER IF EXISTS upload_track_fail')->execute();
        }

        $this->assertTrue(CandidateUpdateRequestService::recordEligibleUpload(1, 'profile_photo', 'new-photo.jpg'));
        $this->assertSame(1, (int) CandidateUpdateRequestUpload::find()->count());
    }

    public function testStagedResponseKeepsTheSavedCivilIdForAnOlderClient()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-back.jpg');

        $front = CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg');
        $olderProfile = [
            'candidate_civil_id' => $front['candidate_civil_id'],
            'candidate_civil_expiry_date' => $front['candidate_civil_expiry_date'],
            'candidate_civil_photo_front' => $front['candidate_civil_photo_front'],
            'candidate_civil_photo_back' => $front['candidate_civil_photo_back'],
        ];

        $this->assertSame('success', $front['operation']);
        $this->assertSame('200000000001', (string) $olderProfile['candidate_civil_id']);
        $this->assertSame('2030-01-01', (string) $olderProfile['candidate_civil_expiry_date']);
        $this->assertSame('old-front.jpg', $olderProfile['candidate_civil_photo_front']);
        $this->assertSame('old-back.jpg', $olderProfile['candidate_civil_photo_back']);
        $this->assertStringContainsString('staging/', $front['staged_photo_front']);
        $this->assertStringNotContainsString('staging/', (string) $front['candidate_civil_photo_front']);
        $this->assertNotNull(CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));

        $back = CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'new-back.jpg');
        $this->assertSame('old-front.jpg', $back['candidate_civil_photo_front']);
        $this->assertStringContainsString('staging/', $back['staged_photo_back']);

        $completed = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $oldId = $completed->cur_uuid;
        $oldRevision = (int) $completed->staging_revision;
        $commit = $this->commitCurrent($candidate, '200000000009', '2031-06-01');
        $candidate->refresh();
        $this->assertSame('success', $commit['operation']);

        $newer = CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'later-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'later-back.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'later-front.jpg');
        CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'later-back.jpg');
        $replay = CandidateUpdateRequestService::commitCivil($candidate, '200000000011', '2032-02-02', $oldId, $oldRevision);
        $candidate->refresh();

        $this->assertSame('error', $replay['operation']);
        $this->assertSame('200000000009', (string) $candidate->candidate_civil_id);
        $this->assertSame('2031-06-01', (string) $candidate->candidate_civil_expiry_date);
        $this->assertSame($newer['request']->cur_uuid, CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->cur_uuid);
    }

    public function testOverlappingStagesCannotShareARevision()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'race-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'race-late.jpg');
        $request = CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);
        $script = tempnam(sys_get_temp_dir(), 'cur') . '.php';
        file_put_contents($script, $this->overlapWorkerSource());

        $hold = proc_open(
            [PHP_BINARY, $script, 'hold-replace', $request->cur_uuid],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $holdPipes
        );
        $this->assertIsResource($hold);
        stream_set_blocking($holdPipes[1], false);
        $heldOutput = '';
        $deadline = time() + 20;
        while (time() < $deadline && strpos($heldOutput, 'HOLDING') === false) {
            $heldOutput .= (string) stream_get_contents($holdPipes[1]);
            usleep(50000);
        }
        $this->assertStringContainsString('HOLDING', $heldOutput);

        $stage = proc_open(
            [PHP_BINARY, $script, 'stage', 'front', 'race-front.jpg'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $stagePipes
        );
        $this->assertIsResource($stage);
        $stageOutput = stream_get_contents($stagePipes[1]);
        $stageError = stream_get_contents($stagePipes[2]);
        $stageCode = proc_close($stage);
        $heldOutput .= (string) stream_get_contents($holdPipes[1]);
        $holdError = stream_get_contents($holdPipes[2]);
        $holdCode = proc_close($hold);

        $staged = json_decode($stageOutput, true);
        $held = json_decode(trim(str_replace('HOLDING', '', $heldOutput)), true);
        $this->assertSame(0, $stageCode, $stageError . $holdError);
        $this->assertSame(0, $holdCode, $holdError);
        $this->assertSame('success', $staged['operation'], $stageOutput . $stageError);
        $this->assertNotSame((int) $held['revision'], (int) $staged['staging_revision']);
        $this->assertSame((int) $held['revision'] + 1, (int) $staged['staging_revision']);

        $saved = CandidateUpdateRequest::findOne($request->cur_uuid);
        $this->assertSame((int) $staged['staging_revision'], (int) $saved->staging_revision);
        $this->assertStringContainsString('race-front.jpg', (string) $saved->staged_front_key);
        $this->assertStringContainsString('race-held-back.jpg', (string) $saved->staged_back_key);

        $replay = CandidateUpdateRequestService::commitCivil(
            $candidate,
            '200000000009',
            '2031-06-01',
            $request->cur_uuid,
            (int) $held['revision']
        );
        $candidate->refresh();
        $this->assertSame('error', $replay['operation']);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
        $this->assertSame('old-front.jpg', $candidate->candidate_civil_photo_front);
        $this->assertSame('old-back.jpg', $candidate->candidate_civil_photo_back);

        $submitHold = proc_open(
            [PHP_BINARY, $script, 'hold-submit', $request->cur_uuid],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $submitPipes
        );
        $this->assertIsResource($submitHold);
        stream_set_blocking($submitPipes[1], false);
        $submitHeld = '';
        $deadline = time() + 20;
        while (time() < $deadline && strpos($submitHeld, 'HOLDING') === false) {
            $submitHeld .= (string) stream_get_contents($submitPipes[1]);
            usleep(50000);
        }
        $this->assertStringContainsString('HOLDING', $submitHeld);

        $late = proc_open(
            [PHP_BINARY, $script, 'stage', 'front', 'race-late.jpg'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $latePipes
        );
        $this->assertIsResource($late);
        $lateOutput = stream_get_contents($latePipes[1]);
        $lateError = stream_get_contents($latePipes[2]);
        $lateCode = proc_close($late);
        proc_close($submitHold);
        @unlink($script);

        $lateResult = json_decode($lateOutput, true);
        $this->assertSame(0, $lateCode, $lateError . $lateOutput);
        $this->assertSame('error', $lateResult['operation'], $lateOutput);
        $saved->refresh();
        $candidate->refresh();
        $this->assertSame(CandidateUpdateRequest::STATUS_SUBMITTED, $saved->status);
        $this->assertStringContainsString('race-front.jpg', (string) $saved->staged_front_key);
        $this->assertStringNotContainsString('race-late.jpg', (string) $saved->staged_front_key);
        $this->assertSame('200000000001', (string) $candidate->candidate_civil_id);
    }

    public function testConcurrentDuplicateCreateKeepsOneRequestAndOneNotification()
    {
        $script = tempnam(sys_get_temp_dir(), 'cur') . '.php';
        file_put_contents($script, $this->concurrentWorkerSource());
        $hold = proc_open(
            [PHP_BINARY, $script, 'hold'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $holdPipes
        );
        $this->assertIsResource($hold);
        stream_set_blocking($holdPipes[1], false);
        $held = '';
        $deadline = time() + 20;
        while (time() < $deadline && strpos($held, 'HOLDING') === false) {
            $held .= (string) stream_get_contents($holdPipes[1]);
            usleep(50000);
        }
        $this->assertStringContainsString('HOLDING', $held);

        $create = proc_open(
            [PHP_BINARY, $script, 'create'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $createPipes
        );
        $this->assertIsResource($create);
        $createOutput = stream_get_contents($createPipes[1]);
        $createError = stream_get_contents($createPipes[2]);
        $createCode = proc_close($create);
        $holdOutput = stream_get_contents($holdPipes[1]);
        $holdError = stream_get_contents($holdPipes[2]);
        proc_close($hold);
        @unlink($script);

        $decoded = json_decode($createOutput, true);
        $this->assertSame(0, $createCode, $createError . $holdError . $holdOutput);
        $this->assertTrue(!empty($decoded['already_pending']));
        $this->assertSame(1, (int) CandidateUpdateRequest::find()->count());
        $this->assertSame(1, (int) CandidateNotification::find()->count());
    }

    /**
     * @return Candidate
     */
    private function pendingCivilCandidate()
    {
        $candidate = Candidate::findOne(1);
        CandidateUpdateRequestService::create($candidate, 1, CandidateUpdateRequest::TYPE_CIVIL_ID);

        return $candidate;
    }

    /**
     * @return Candidate
     */
    private function stageBothSides()
    {
        $candidate = $this->pendingCivilCandidate();
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-back.jpg');
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg')['operation']);
        $this->assertSame('success', CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'new-back.jpg')['operation']);

        return $candidate;
    }

    /**
     * @param Candidate $candidate
     * @param string $civilId
     * @param string $expiry
     * @return array|null
     */
    private function commitCurrent(Candidate $candidate, $civilId, $expiry)
    {
        $request = CandidateUpdateRequestService::pending(
            (int) $candidate->candidate_id,
            CandidateUpdateRequest::TYPE_CIVIL_ID
        );
        $this->assertNotNull($request);

        return CandidateUpdateRequestService::commitCivil(
            $candidate,
            $civilId,
            $expiry,
            $request->cur_uuid,
            (int) $request->staging_revision
        );
    }

    private static function installSchema()
    {
        $db = Yii::$app->db;
        $db->createCommand('SET FOREIGN_KEY_CHECKS=0')->execute();
        foreach ([
            'candidate_update_request_upload',
            'candidate_update_request',
            'candidate_notification',
            'candidate_education',
            'candidate_skill',
            'area',
            'country',
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
            candidate_uid VARCHAR(64) NULL,
            candidate_name VARCHAR(255) NULL,
            candidate_name_ar VARCHAR(255) NULL,
            candidate_email VARCHAR(255) NULL,
            candidate_phone VARCHAR(64) NULL,
            candidate_gender INT NULL,
            candidate_objective VARCHAR(255) NULL,
            candidate_birth_date DATE NULL,
            candidate_driving_license VARCHAR(64) NULL,
            candidate_latitude VARCHAR(64) NULL,
            candidate_longitude VARCHAR(64) NULL,
            candidate_area_uuid VARCHAR(60) NULL,
            candidate_mom_kuwaiti TINYINT NULL,
            country_id INT NULL,
            candidate_iban VARCHAR(64) NULL,
            bank_id INT NULL,
            currency_code VARCHAR(8) NULL,
            ip_address VARCHAR(64) NULL,
            candidate_created_at DATETIME NULL,
            candidate_updated_at DATETIME NULL,
            is_incomplete_profile TINYINT NULL,
            candidate_pending_profile VARCHAR(1000) NULL,
            candidate_personal_photo VARCHAR(255) NULL,
            candidate_civil_id VARCHAR(255) NULL,
            candidate_civil_expiry_date DATE NULL,
            candidate_civil_photo_front VARCHAR(255) NULL,
            candidate_civil_photo_back VARCHAR(255) NULL,
            candidate_civil_need_verification TINYINT NULL,
            candidate_job_search_status INT NULL,
            deleted TINYINT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE candidate_education (
            education_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            candidate_id INT NOT NULL
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE candidate_skill (
            candidate_skill_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            candidate_id INT NOT NULL,
            deleted TINYINT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE area (
            area_uuid VARCHAR(60) NOT NULL PRIMARY KEY,
            country_id INT NULL
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE country (
            country_id INT NOT NULL PRIMARY KEY,
            country_nationality_name_en VARCHAR(255) NULL
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
            'candidate_civil_need_verification' => 0,
            'deleted' => 0,
        ])->execute();
        $db->createCommand()->insert('candidate', [
            'candidate_id' => 2,
            'candidate_civil_id' => '200000000002',
            'deleted' => 0,
        ])->execute();

        $migration = new \m261015_140000_candidate_update_requests();
        $migration->up();
    }

    private static function repositoryAutoload($root)
    {
        $autoload = $root . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new \RuntimeException('Composer autoload was not found at ' . $autoload . '. Run composer install in this repository.');
        }

        return $autoload;
    }

    private function concurrentWorkerSource()
    {
        $root = dirname(__DIR__, 4);
        $rootExport = var_export($root, true);

        return <<<PHP
<?php
\$root = {$rootExport};
\$vendor = \$root . '/vendor/autoload.php';
if (!is_file(\$vendor)) {
    fwrite(STDERR, "Composer autoload was not found.\\n");
    exit(1);
}
require \$vendor;
require dirname(\$vendor) . '/yiisoft/yii2/Yii.php';
new yii\console\Application([
    'id' => 'update-request-worker',
    'basePath' => \$root . '/common',
    'aliases' => ['@common' => \$root . '/common'],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => getenv('UPDATE_REQUEST_TEST_DSN'),
            'username' => getenv('UPDATE_REQUEST_TEST_USER') ?: 'root',
            'password' => getenv('UPDATE_REQUEST_TEST_PASSWORD') ?: 'root',
            'charset' => 'utf8mb4',
        ],
    ],
]);
\$mode = \$argv[1] ?? '';
if (\$mode === 'hold') {
    \$db = Yii::\$app->db;
    \$tx = \$db->beginTransaction();
    \$db->createCommand()->insert('candidate_update_request', [
        'cur_uuid' => 'cur_holdconcurrent0001',
        'candidate_id' => 1,
        'request_type' => 'profile_photo',
        'status' => 'pending',
        'staff_id' => 1,
        'requested_at' => date('Y-m-d H:i:s'),
        'open_slot' => '1:profile_photo',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ])->execute();
    \$db->createCommand()->insert('candidate_notification', [
        'cn_uuid' => 'cn_holdconcurrent0001',
        'candidate_id' => 1,
        'type' => 12,
        'staff_id' => 1,
        'is_new' => 1,
        'update_request_uuid' => 'cur_holdconcurrent0001',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ])->execute();
    fwrite(STDOUT, "HOLDING\n");
    fflush(STDOUT);
    sleep(8);
    \$tx->commit();
    fwrite(STDOUT, "COMMITTED\n");
    exit(0);
}
\$candidate = common\models\Candidate::findOne(1);
\$result = common\components\CandidateUpdateRequestService::create(
    \$candidate,
    1,
    common\models\CandidateUpdateRequest::TYPE_PROFILE_PHOTO
);
fwrite(STDOUT, json_encode(['already_pending' => \$result['already_pending']]));
exit(0);
PHP;
    }

    private function overlapWorkerSource()
    {
        $root = dirname(__DIR__, 4);
        $rootExport = var_export($root, true);

        return <<<PHP
<?php
if (!defined('YII_DEBUG')) {
    define('YII_DEBUG', true);
}
if (!defined('YII_ENV')) {
    define('YII_ENV', 'test');
}
\$root = {$rootExport};
\$vendor = \$root . '/vendor/autoload.php';
if (!is_file(\$vendor)) {
    fwrite(STDERR, "Composer autoload was not found.\\n");
    exit(1);
}
require \$vendor;
require dirname(\$vendor) . '/yiisoft/yii2/Yii.php';
new yii\console\Application([
    'id' => 'update-request-overlap',
    'basePath' => \$root . '/common',
    'aliases' => ['@common' => \$root . '/common'],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => getenv('UPDATE_REQUEST_TEST_DSN'),
            'username' => getenv('UPDATE_REQUEST_TEST_USER') ?: 'root',
            'password' => getenv('UPDATE_REQUEST_TEST_PASSWORD') ?: 'root',
            'charset' => 'utf8mb4',
        ],
        'i18n' => [
            'translations' => [
                'candidate*' => ['class' => 'yii\i18n\PhpMessageSource', 'forceTranslation' => false],
                'app*' => ['class' => 'yii\i18n\PhpMessageSource', 'forceTranslation' => false],
            ],
        ],
    ],
]);
\$storage = new class extends yii\base\Component {
    public \$bucket = 'race-test';
    public function copy(\$oldFile, \$newFile, \$sourceBucket = '', \$options = []) {}
    public function delete(\$name) {}
};
Yii::\$app->set('resourceManager', \$storage);
Yii::\$app->set('temporaryBucketResourceManager', \$storage);
\$mode = \$argv[1] ?? '';
\$uuid = \$argv[2] ?? '';
\$db = Yii::\$app->db;
if (\$mode === 'hold-replace' || \$mode === 'hold-submit') {
    \$tx = \$db->beginTransaction();
    \$row = \$db->createCommand(
        'SELECT staging_revision FROM candidate_update_request WHERE cur_uuid = :id FOR UPDATE',
        [':id' => \$uuid]
    )->queryOne();
    fwrite(STDOUT, "HOLDING\n");
    fflush(STDOUT);
    sleep(8);
    if (\$mode === 'hold-replace') {
        \$updated = \$db->createCommand()->update('candidate_update_request', [
            'staged_back_key' => 'photos/staging/' . \$uuid . '/back/race-held-back.jpg',
            'staged_back_source_sha256' => str_repeat('a', 64),
            'staging_revision' => new yii\db\Expression('[[staging_revision]] + 1'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], [
            'cur_uuid' => \$uuid,
            'status' => 'pending',
            'staging_revision' => (int) \$row['staging_revision'],
        ])->execute();
        \$tx->commit();
        \$revision = (int) \$db->createCommand(
            'SELECT staging_revision FROM candidate_update_request WHERE cur_uuid = :id',
            [':id' => \$uuid]
        )->queryScalar();
        fwrite(STDOUT, json_encode(['updated' => (int) \$updated, 'revision' => \$revision]));
        exit(0);
    }
    \$db->createCommand()->update('candidate_update_request', [
        'status' => 'submitted',
        'open_slot' => null,
        'submitted_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['cur_uuid' => \$uuid, 'status' => 'pending'])->execute();
    \$tx->commit();
    fwrite(STDOUT, "SUBMITTED\n");
    exit(0);
}
\$candidate = common\models\Candidate::findOne(1);
\$result = common\components\CandidateUpdateRequestService::stageCivilSide(\$candidate, \$argv[2], \$argv[3]);
fwrite(STDOUT, json_encode(\$result));
exit(0);
PHP;
    }
}
