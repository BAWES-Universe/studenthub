<?php

namespace common\tests\unit\models;

use common\models\Company;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;

class CompanyDocumentStorageTest extends TestCase
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

    public function testClassificationKeepsLegacyReferencesDistinctFromPermanentKeys()
    {
        $this->assertSame('legacy', Company::classifyCommercialLicence('commercial-licence/old.jpg'));
        $this->assertSame('permanent', Company::classifyCommercialLicence('company-commercial-licences/new.jpg'));
        $this->assertSame('permanent', Company::classifyCommercialLicence('dev/company-commercial-licences/new.jpg'));
        $this->assertSame('temporary', Company::classifyCommercialLicence('licence-1.png'));
        $this->assertSame('unknown', Company::classifyCommercialLicence('https://res.cloudinary.com/studenthub/image/upload/commercial-licence/old.jpg'));
        $this->assertSame('legacy', Company::classifyCompanyLogo('company-logo/old.jpg'));
        $this->assertSame('permanent', Company::classifyCompanyLogo('company-logos/new.jpg'));
        $this->assertFalse(Company::shouldValidateTemporaryUpload('commercial-licence/old.jpg', 'commercial-licence/other.jpg', 'licence'));
        $this->assertTrue(Company::shouldValidateTemporaryUpload('licence-1.png', 'commercial-licence/old.jpg', 'licence'));
    }

    public function testNewLicenceIsCopiedToThePermanentPrefixWithoutCloudinary()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $company->commercial_licence = 'licence-1.png';
        $managers = $this->installManagers();

        $this->assertTrue($company->updateLicence());
        $this->assertSame(Company::permanentDocumentPrefix('licence') . 'licence-1.png', $company->commercial_licence);
        $this->assertSame(['licence-1.png', Company::permanentDocumentPrefix('licence') . 'licence-1.png', 'temp-bucket'], $managers->copies[0]);
        $this->assertSame([], $managers->cloudinaryDeletes);
        $this->assertSame('commercial-licence/old.jpg', $company->getOldAttribute('commercial_licence'));
    }

    public function testFailedCopyKeepsThePreviousLicence()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $company->commercial_licence = 'missing.png';
        $this->installManagers(false);

        $this->assertFalse($company->updateLicence());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertArrayHasKey('commercial_licence', $company->getErrors());
    }

    public function testUnchangedLicenceAndLogoAreNotCopied()
    {
        $company = $this->company('company-commercial-licences/kept.jpg', 'company-logos/kept.jpg');
        $managers = $this->installManagers();

        $this->assertTrue($company->beforeSave(false));
        $this->assertSame([], $managers->copies);
        $this->assertSame('company-commercial-licences/kept.jpg', $company->commercial_licence);
        $this->assertSame('company-logos/kept.jpg', $company->company_logo);
    }

    public function testLegacyLicenceUrlUsesCloudinaryAndDoesNotRewriteTheKey()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $this->installManagers();
        Yii::$app->cloudinaryManager->cloud_name = 'test-cloud';

        $url = $company->getCommercialLicenceUrl();

        $this->assertStringContainsString('res.cloudinary.com/test-cloud/image/upload/', $url);
        $this->assertStringContainsString('commercial-licence/old.jpg', $url);
        $this->assertStringNotContainsString('company-commercial-licences/', $url);
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
    }

    public function testLegacyLicenceWithoutCloudNameHasNoDisplayUrl()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $this->installManagers();
        Yii::$app->cloudinaryManager->cloud_name = null;

        $this->assertNull($company->getCommercialLicenceUrl());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
    }

    public function testPermanentLicenceUrlComesFromThePermanentBucket()
    {
        $key = Company::permanentDocumentPrefix('licence') . 'licence-1.png';
        $company = $this->company($key, null);
        $this->installManagers();

        $this->assertSame('https://uploads.example/' . $key, $company->getCommercialLicenceUrl());
    }

    public function testReplacingALicenceDeletesTheOldObjectOnlyAfterSave()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $company->commercial_licence = 'licence-2.png';
        $managers = $this->installManagers();

        $this->assertTrue($company->updateLicence());
        $this->assertSame([], $managers->cloudinaryDeletes);

        $company->afterSave(false, ['commercial_licence' => 'commercial-licence/old.jpg']);

        $legacyPath = (YII_ENV == 'prod' ? '' : 'dev/') . 'commercial-licence/old.jpg';
        $this->assertSame([$legacyPath], $managers->cloudinaryDeletes);
        $this->assertSame(Company::permanentDocumentPrefix('licence') . 'licence-2.png', $company->commercial_licence);
    }

    private function boot(): void
    {
        if (!class_exists(Yii::class, false)) {
            $root = dirname(__DIR__, 4);
            require_once $root . '/vendor/autoload.php';
            require_once $root . '/vendor/yiisoft/yii2/Yii.php';
            require_once $root . '/common/config/bootstrap.php';
            defined('YII_DEBUG') or define('YII_DEBUG', true);
            defined('YII_ENV') or define('YII_ENV', 'test');
        }

        new Application([
            'id' => 'company-document-storage-test',
            'basePath' => dirname(__DIR__, 4) . '/common',
            'vendorPath' => dirname(__DIR__, 4) . '/vendor',
            'components' => [
                'db' => [
                    'class' => \yii\db\Connection::class,
                    'dsn' => 'sqlite::memory:',
                ],
                'request' => [
                    'cookieValidationKey' => 'company-document-storage-test',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/index.php',
                ],
            ],
        ]);
        Yii::$app->set('eventManager', new class extends \yii\base\Component {
            public function track($name, $attributes)
            {
            }
        });

        Yii::$app->db->createCommand(
            'CREATE TABLE company (
                company_id INTEGER PRIMARY KEY,
                commercial_licence TEXT,
                company_logo TEXT,
                currency_code TEXT,
                company_last_followup_datetime TEXT,
                company_followup_interval_weeks INTEGER,
                company_next_followup_datetime TEXT,
                company_updated_at TEXT,
                company_created_at TEXT
            )'
        )->execute();
    }

    private function company(?string $licence, ?string $logo): Company
    {
        $company = new Company();
        $company->setOldAttributes([
            'commercial_licence' => $licence,
            'company_logo' => $logo,
            'company_last_followup_datetime' => null,
            'company_followup_interval_weeks' => null,
        ]);
        $company->commercial_licence = $licence;
        $company->company_logo = $logo;
        $company->currency_code = 'KWD';
        $company->isNewRecord = false;

        return $company;
    }

    private function installManagers(bool $copySucceeds = true)
    {
        $managers = new class($copySucceeds) {
            public $copies = [];
            public $cloudinaryDeletes = [];
            public $bucket = 'temp-bucket';
            public $cloud_name;
            private $copySucceeds;

            public function __construct($copySucceeds)
            {
                $this->copySucceeds = $copySucceeds;
            }

            public function fileExists($key)
            {
                return $key !== 'missing.png';
            }

            public function copy($oldFile, $newFile, $sourceBucket = '')
            {
                if (!$this->copySucceeds || $oldFile === 'missing.png') {
                    throw new \RuntimeException('copy failed');
                }
                $this->copies[] = [$oldFile, $newFile, $sourceBucket];
            }

            public function getUrl($name)
            {
                return 'https://uploads.example/' . $name;
            }

            public function delete($name)
            {
                $this->cloudinaryDeletes[] = $name;
            }
        };

        Yii::$app->set('temporaryBucketResourceManager', $managers);
        Yii::$app->set('resourceManager', $managers);
        Yii::$app->set('cloudinaryManager', $managers);

        return $managers;
    }
}
