<?php

namespace common\tests\unit\models;

use common\models\Candidate;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;

class CandidateCivilIdStorageTest extends TestCase
{
    /** @var Application|null */
    private $previousApp;

    /** @var array */
    private $previousAliases = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousApp = class_exists(Yii::class, false) ? Yii::$app : null;
        $this->previousAliases = class_exists(Yii::class, false) ? Yii::$aliases : [];
        $this->boot();
    }

    protected function tearDown(): void
    {
        if (class_exists(Yii::class, false) && Yii::$app instanceof Application && Yii::$app !== $this->previousApp) {
            Yii::$app->getErrorHandler()->unregister();
        }
        Yii::$app = $this->previousApp;
        if (class_exists(Yii::class, false)) {
            Yii::$aliases = $this->previousAliases;
        }
        parent::tearDown();
    }

    public function testStoredKeysNormalizeToThePhotosPrefix()
    {
        $this->assertSame('photos/a.png', Candidate::normalizeCivilIdPermanentS3Key('a.png'));
        $this->assertSame('photos/a.png', Candidate::normalizeCivilIdPermanentS3Key('photos/a.png'));
        $this->assertSame('photos/a.png', Candidate::normalizeCivilIdPermanentS3Key('candidate-civil-id/a.png'));
        $this->assertSame('', Candidate::normalizeCivilIdPermanentS3Key(null));
    }

    public function testReplacementCopiesAndVerifiesBeforeTheOldFileIsDeletedAfterSave()
    {
        $managers = $this->installManagers();
        $candidate = $this->candidate('old-front.png', 'old-back.png');
        $candidate->candidate_civil_photo_front = 'new-front.png';

        $this->assertTrue($candidate->updateCivilId('front'));
        $this->assertSame([['new-front.png', 'photos/new-front.png', 'temp-bucket']], $managers->copies);
        $this->assertSame(['photos/new-front.png'], $managers->heads);
        $this->assertSame([], $managers->deletes, 'The old file must survive until the record is saved');

        $candidate->afterSave(false, []);

        $this->assertSame(['photos/old-front.png'], $managers->deletes);

        $candidate->afterSave(false, []);
        $this->assertSame(['photos/old-front.png'], $managers->deletes, 'A replaced file is deleted once');
    }

    public function testFailedCopyKeepsTheOldFileAndReportsOnTheCivilIdField()
    {
        $managers = $this->installManagers();
        $candidate = $this->candidate('old-front.png', 'old-back.png');
        $candidate->candidate_civil_photo_back = 'missing.png';

        $this->assertFalse($candidate->updateCivilId('back'));
        $this->assertTrue($candidate->hasErrors('candidate_civil_photo_back'));
        $this->assertFalse($candidate->hasErrors('candidate_resume'));
        $this->assertSame([], $managers->heads);

        $candidate->deleteReplacedCivilIdFiles();
        $this->assertSame([], $managers->deletes);
    }

    public function testUnverifiedCopyKeepsTheOldFile()
    {
        $managers = $this->installManagers();
        $managers->unverifiable[] = 'photos/new-front.png';
        $candidate = $this->candidate('old-front.png', null);
        $candidate->candidate_civil_photo_front = 'new-front.png';

        $this->assertFalse($candidate->updateCivilId('front'));
        $this->assertTrue($candidate->hasErrors('candidate_civil_photo_front'));

        $candidate->deleteReplacedCivilIdFiles();
        $this->assertSame([], $managers->deletes);
    }

    public function testLegacyPrefixIsDeletedFromPhotosAndAMissingObjectIsTolerated()
    {
        $managers = $this->installManagers();
        $managers->missingOnDelete[] = 'photos/old-back.png';
        $candidate = $this->candidate(null, 'candidate-civil-id/old-back.png');
        $candidate->candidate_civil_photo_back = 'new-back.png';

        $this->assertTrue($candidate->updateCivilId('back'));
        $candidate->deleteReplacedCivilIdFiles();

        $this->assertSame(['photos/old-back.png'], $managers->deleteAttempts);
        $this->assertSame([], $managers->deletes);
        $this->assertFalse($candidate->hasErrors());
    }

    public function testSameKeyIsNotDeleted()
    {
        $managers = $this->installManagers();
        $candidate = $this->candidate('photos/same.png', null);
        $candidate->candidate_civil_photo_front = 'same.png';

        $this->assertTrue($candidate->updateCivilId('front'));
        $candidate->deleteReplacedCivilIdFiles();

        $this->assertSame([], $managers->deleteAttempts);
    }

    public function testCivilIdDeleteFileUsesPhotosPrefixAndNeverFlagsTheResume()
    {
        $managers = $this->installManagers();
        $managers->missingOnDelete[] = 'photos/old-front.png';
        $candidate = $this->candidate('candidate-civil-id/old-front.png', 'old-back.png');

        $this->assertTrue($candidate->deleteFile('civil-id', 'front'));
        $this->assertTrue($candidate->deleteFile('civil-id', 'back'));

        $this->assertSame(['photos/old-front.png', 'photos/old-back.png'], $managers->deleteAttempts);
        $this->assertFalse($candidate->hasErrors());
    }

    private function candidate(?string $front, ?string $back): Candidate
    {
        $candidate = new Candidate();
        $candidate->setOldAttributes([
            'candidate_id' => 7,
            'candidate_civil_photo_front' => $front,
            'candidate_civil_photo_back' => $back,
        ]);
        $candidate->candidate_civil_photo_front = $front;
        $candidate->candidate_civil_photo_back = $back;

        return $candidate;
    }

    private function boot(): void
    {
        $root = dirname(__DIR__, 4);
        if (!class_exists(Yii::class, false)) {
            defined('YII_DEBUG') or define('YII_DEBUG', true);
            defined('YII_ENV') or define('YII_ENV', 'test');
            require_once $root . '/vendor/autoload.php';
            require_once $root . '/vendor/yiisoft/yii2/Yii.php';
        }
        include $root . '/common/config/bootstrap.php';

        new Application([
            'id' => 'candidate-civil-id-storage-test',
            'basePath' => $root . '/common',
            'vendorPath' => $root . '/vendor',
            'components' => [
                'db' => [
                    'class' => \yii\db\Connection::class,
                    'dsn' => 'sqlite::memory:',
                ],
                'request' => [
                    'cookieValidationKey' => 'candidate-civil-id-storage-test',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/index.php',
                ],
                'user' => [
                    'identityClass' => Candidate::class,
                    'enableSession' => false,
                    'enableAutoLogin' => false,
                ],
            ],
        ]);
        Yii::$app->set('eventManager', new class extends \yii\base\Component {
            public function track($event, $eventData, $timestamp = null, $userId = null, $onlyWallet = false)
            {
            }
        });
        Yii::$app->db->createCommand(
            'CREATE TABLE candidate (
                candidate_id INTEGER PRIMARY KEY,
                candidate_resume TEXT,
                candidate_civil_photo_front TEXT,
                candidate_civil_photo_back TEXT,
                candidate_job_search_status INTEGER,
                deleted INTEGER
            )'
        )->execute();
    }

    private function installManagers()
    {
        $managers = new class {
            public $bucket = 'temp-bucket';
            public $copies = [];
            public $heads = [];
            public $deletes = [];
            public $deleteAttempts = [];
            public $unverifiable = [];
            public $missingOnDelete = [];

            public function copy($oldFile, $newFile, $sourceBucket = '', $options = [])
            {
                if ($oldFile === 'missing.png') {
                    throw new \RuntimeException('NoSuchKey');
                }
                $this->copies[] = [$oldFile, $newFile, $sourceBucket];
            }

            public function storedObjectExists($key)
            {
                $this->heads[] = $key;

                return !in_array($key, $this->unverifiable, true);
            }

            public function delete($name)
            {
                $this->deleteAttempts[] = $name;
                if (in_array($name, $this->missingOnDelete, true)) {
                    throw new \RuntimeException('NoSuchKey: The specified key does not exist.');
                }
                $this->deletes[] = $name;
            }
        };

        Yii::$app->set('temporaryBucketResourceManager', $managers);
        Yii::$app->set('resourceManager', $managers);

        return $managers;
    }
}
