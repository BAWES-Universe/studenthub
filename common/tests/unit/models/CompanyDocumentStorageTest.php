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
        $this->assertSame('public-copy', Company::classifyCommercialLicence('company-commercial-licences/new.jpg'));
        $this->assertSame('public-copy', Company::classifyCommercialLicence('dev/company-commercial-licences/new.jpg'));
        $this->assertSame('permanent', Company::classifyCommercialLicence(Company::permanentDocumentPrefix('licence') . 'new.jpg'));
        $this->assertSame('temporary', Company::classifyCommercialLicence('licence-1.png'));
        $this->assertFalse(Company::isPrivateTemporaryLicenceKey('licence-1.png'));
        $this->assertTrue(Company::isPrivateTemporaryLicenceKey(Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'licence-1.png'));
        $this->assertSame(
            \common\components\TempUploadPresigner::PRIVATE_LICENCE_KEY_PREFIX,
            Company::PRIVATE_TEMPORARY_LICENCE_PREFIX
        );
        $this->assertSame('unknown', Company::classifyCommercialLicence('https://res.cloudinary.com/studenthub/image/upload/commercial-licence/old.jpg'));
        $this->assertSame('legacy', Company::classifyCompanyLogo('company-logo/old.jpg'));
        $this->assertSame('permanent', Company::classifyCompanyLogo('company-logos/new.jpg'));
        $this->assertFalse(Company::shouldValidateTemporaryUpload('commercial-licence/old.jpg', 'commercial-licence/other.jpg', 'licence'));
        $this->assertTrue(Company::shouldValidateTemporaryUpload('licence-1.png', 'commercial-licence/old.jpg', 'licence'));
    }

    public function testNewLicenceIsCopiedToThePermanentPrefixWithoutCloudinary()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $tempKey = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'licence-1.png';
        $company->commercial_licence = $tempKey;
        $managers = $this->installManagers();

        $this->assertTrue($company->updateLicence());
        $this->assertSame(Company::permanentDocumentPrefix('licence') . $tempKey, $company->commercial_licence);
        $this->assertSame([
            $tempKey,
            Company::permanentDocumentPrefix('licence') . $tempKey,
            'temp-bucket',
            ['ACL' => 'private'],
        ], $managers->copies[0]);
        $this->assertSame([['temp-bucket', $tempKey]], $managers->authenticatedHeads);
        $this->assertSame([], $managers->sdkHeads);
        $this->assertSame([], $managers->cloudinaryDeletes);
        $this->assertSame('commercial-licence/old.jpg', $company->getOldAttribute('commercial_licence'));
    }

    public function testPublicTemporaryLicenceIsNotPromoted()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $company->commercial_licence = 'licence-public.png';
        $managers = $this->installManagers();

        $this->assertFalse($company->updateLicence());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertSame([], $managers->authenticatedHeads);
        $this->assertSame([], $managers->copies);
    }

    public function testAnonymouslyReadableLicenceIsNotPromoted()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $tempKey = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'public-temp-licence.png';
        $company->commercial_licence = $tempKey;
        $managers = $this->installManagers();

        $this->assertFalse($company->updateLicence());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertSame([$tempKey], $managers->anonymousChecks);
        $this->assertSame([], $managers->authenticatedHeads);
    }

    public function testUncertainAnonymousCheckDoesNotPromote()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $tempKey = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'uncertain-licence.png';
        $company->commercial_licence = $tempKey;
        $managers = $this->installManagers();

        $this->assertFalse($company->updateLicence());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertSame([$tempKey], $managers->anonymousChecks);
        $this->assertSame([], $managers->authenticatedHeads);
        $this->assertSame([], $managers->copies);
    }

    public function testLogoPromotionStillUsesThePublicExistenceCheck()
    {
        $company = $this->company(null, 'company-logo/old.jpg');
        $company->company_logo = 'logo-new.png';
        $managers = $this->installManagers();

        $this->assertTrue($company->updateCompanyLogo());
        $this->assertSame([], $managers->authenticatedHeads);
        $this->assertSame([], $managers->anonymousChecks);
        $this->assertSame(Company::permanentDocumentPrefix('logo') . 'logo-new.png', $company->company_logo);
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

        $this->assertNull($company->getCommercialLicenceUrl());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertStringContainsString('res.cloudinary.com/test-cloud/image/upload/', $company->getCompanyLogoUrl());
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

        $this->assertNull($company->getCommercialLicenceUrl());
        $this->assertSame('https://uploads.example/company-logos/kept.jpg', $this->company(null, 'company-logos/kept.jpg')->getCompanyLogoUrl());
    }

    public function testReplacingALicenceKeepsThePreviousObject()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $tempKey = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'licence-2.png';
        $company->commercial_licence = $tempKey;
        $managers = $this->installManagers();

        $this->assertTrue($company->updateLicence());
        $company->afterSave(false, ['commercial_licence' => 'commercial-licence/old.jpg']);

        $this->assertSame([], $managers->cloudinaryDeletes);
        $this->assertSame(Company::permanentDocumentPrefix('licence') . $tempKey, $company->commercial_licence);
    }

    public function testOuterTransactionRollbackKeepsThePreviousLicence()
    {
        $managers = $this->installManagers();
        Yii::$app->db->createCommand()->insert('company', [
            'company_id' => 1,
            'commercial_licence' => 'commercial-licence/old.jpg',
            'company_logo' => 'company-logo/old.jpg',
            'currency_code' => 'KWD',
        ])->execute();
        $company = new Company();
        $company->company_id = 1;
        $company->commercial_licence = 'commercial-licence/old.jpg';
        $company->company_logo = 'company-logo/old.jpg';
        $company->currency_code = 'KWD';
        $company->isNewRecord = false;
        $company->setOldAttributes([
            'company_id' => 1,
            'commercial_licence' => 'commercial-licence/old.jpg',
            'company_logo' => 'company-logo/old.jpg',
            'currency_code' => 'KWD',
            'company_last_followup_datetime' => null,
            'company_followup_interval_weeks' => null,
        ]);
        $company->detachBehaviors();

        $transaction = Yii::$app->db->beginTransaction();
        $company->commercial_licence = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'licence-2.png';
        $this->assertTrue($company->save(false));
        $this->assertNotEmpty($managers->copies);
        $transaction->rollBack();

        $stored = Yii::$app->db->createCommand(
            'SELECT commercial_licence, company_logo FROM company WHERE company_id = 1'
        )->queryOne();
        $this->assertSame('commercial-licence/old.jpg', $stored['commercial_licence']);
        $this->assertSame('company-logo/old.jpg', $stored['company_logo']);
        $this->assertSame([], $managers->cloudinaryDeletes);
    }

    public function testFailedLicenceCopyRestoresALogoCopiedInTheSameSave()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $company->company_logo = 'logo-new.png';
        $company->commercial_licence = 'missing.png';
        $managers = $this->installManagers();

        $this->assertFalse($company->beforeSave(false));
        $this->assertSame('company-logo/old.jpg', $company->company_logo);
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertSame([], $managers->cloudinaryDeletes);
        $this->assertNotEmpty($managers->copies);
    }

    public function testReplacementValidationRejectsForeignAndLegacyKeys()
    {
        $company = $this->company('company-commercial-licences/mine.jpg', 'company-logos/mine.jpg');
        $company->scenario = Company::SCENARIO_REPLACE_LICENCE;
        $managers = $this->installManagers();

        $company->commercial_licence = 'company-commercial-licences/theirs.jpg';
        $this->assertFalse($company->validate());

        $company->clearErrors();
        $company->commercial_licence = 'commercial-licence/someone-else.jpg';
        $this->assertFalse($company->validate());

        $company->clearErrors();
        $company->commercial_licence = 'missing.png';
        $this->assertFalse($company->validate());

        $company->clearErrors();
        $company->commercial_licence = 'company-commercial-licences/mine.jpg';
        $this->assertTrue($company->validate());
        $this->assertSame([], $managers->copies);
    }

    public function testOmittedDocumentFieldPreservesTheStoredKey()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $this->installManagers();

        Company::assignPostedDocumentValue($company, ['name' => 'Acme'], 'commercial_licence', 'commercial_licence');
        Company::assignPostedDocumentValue($company, ['logo' => ''], 'logo', 'company_logo');

        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertSame('', $company->company_logo);
        $scenarios = $company->scenarios();
        $this->assertNotContains('commercial_licence', $scenarios[Company::SCENARIO_UPDATE]);
        $this->assertNotContains('company_logo', $scenarios[Company::SCENARIO_UPDATE]);
    }

    public function testAdminUpdateWithoutLicenceKeepsTheStoredLicence()
    {
        $company = $this->company('commercial-licence/kept.jpg', 'company-logo/old.jpg');
        $this->installManagers();
        $params = [
            'name' => 'Acme',
            'logo' => 'company-logo/old.jpg',
        ];

        Company::assignPostedDocumentValue($company, $params, 'logo', 'company_logo');
        Company::assignPostedDocumentValue($company, $params, 'commercial_licence', 'commercial_licence');

        $this->assertSame('commercial-licence/kept.jpg', $company->commercial_licence);
        $this->assertSame('company-logo/old.jpg', $company->company_logo);
    }

    public function testCopyThrowOnAnExistingTemporaryObjectKeepsThePreviousLicence()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $tempKey = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'licence-exists.png';
        $company->commercial_licence = $tempKey;
        $managers = $this->installManagers(true, [$tempKey]);

        $this->assertTrue($managers->fileExists($tempKey));
        $this->assertFalse($company->updateLicence());
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertArrayHasKey('commercial_licence', $company->getErrors());
        $this->assertSame([], $managers->copies);
    }

    public function testFailedLicencePromotionRestoresAnOriginallyNullLogo()
    {
        $company = $this->company('commercial-licence/old.jpg', null);
        $company->company_logo = 'logo-new.png';
        $tempKey = Company::PRIVATE_TEMPORARY_LICENCE_PREFIX . 'licence-exists.png';
        $company->commercial_licence = $tempKey;
        $this->installManagers(true, [$tempKey]);

        $this->assertFalse($company->beforeSave(false));
        $this->assertNull($company->company_logo);
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
    }

    public function testCompanyNotificationUsesTheResolvedLogoAndFileTransport()
    {
        $company = $this->company(null, 'dev/company-logos/a&b.png');
        $company->company_name = 'Local Logo';
        $this->installManagers();
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'company-logo-mail-' . uniqid('', true);
        mkdir($dir, 0775, true);
        Yii::$app->set('mailer', [
            'class' => \yii\symfonymailer\Mailer::class,
            'useFileTransport' => true,
            'fileTransportPath' => $dir,
            'viewPath' => '@common/mail',
            'htmlLayout' => false,
        ]);

        $sent = Yii::$app->mailer->compose('report-company-crud', [
            'model' => $company,
            'logo' => 'https://example.test/brand.png',
            'title' => 'Local notice',
            'staff_name' => 'Local Staff',
            'type' => 'created',
        ])->setFrom('local@example.test')
            ->setTo('logo-check@example.test')
            ->setSubject('Local notice')
            ->send();

        $this->assertTrue($sent);
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.eml');
        $this->assertCount(1, $files);
        $body = file_get_contents($files[0]);
        $this->assertStringContainsString('logo-check@example.test', $body);
        $this->assertStringContainsString('https://uploads.example/dev/company-logos/a&amp;b.png', $body);
        $this->assertStringNotContainsString('res.cloudinary.com', $body);
        $this->assertStringNotContainsString('candidate_photo', $body);
    }

    public function testCompanyNotificationOmitsAnUnavailableLogo()
    {
        $company = $this->company(null, null);
        $company->company_name = 'Local Logo';
        $this->installManagers();
        $html = Yii::$app->view->renderFile('@common/mail/report-company-crud.php', [
            'model' => $company,
            'logo' => 'https://example.test/brand.png',
            'title' => 'Local notice',
            'staff_name' => 'Local Staff',
            'type' => 'created',
        ]);

        $this->assertStringNotContainsString('width="100"', $html);
        $this->assertStringNotContainsString('res.cloudinary.com', $html);
        $this->assertStringContainsString('https://example.test/brand.png', $html);
    }

    public function testOnlyStaffCanReplaceOrRemoveCompanyDocuments()
    {
        $managers = $this->installManagers();
        Yii::$app->user->switchIdentity(new \common\models\Admin());
        $company = $this->company('commercial-licence/old.jpg', 'company-logos/kept.jpg');
        $company->commercial_licence = 'licence-2.png';
        $company->company_logo = 'logo-new.png';

        $this->assertFalse($company->beforeSave(false));
        $this->assertSame('commercial-licence/old.jpg', $company->commercial_licence);
        $this->assertSame('company-logos/kept.jpg', $company->company_logo);
        $this->assertSame([], $managers->copies);

        Yii::$app->user->switchIdentity(null);
        $cleared = $this->company('commercial-licence/old.jpg', 'company-logos/kept.jpg');
        $cleared->commercial_licence = '';
        $this->assertFalse($cleared->beforeSave(false));
        $this->assertSame('commercial-licence/old.jpg', $cleared->commercial_licence);

        Yii::$app->user->switchIdentity(new \common\models\Admin());
        $same = $this->company('commercial-licence/old.jpg', 'company-logos/kept.jpg');
        $this->assertTrue($same->beforeSave(false));
        $this->assertSame([], $managers->copies);
    }

    public function testAuthenticatedReaderReturnsStoredBytesAndNotAPublicUrl()
    {
        $this->installManagers();
        $company = $this->company(Company::permanentDocumentPrefix('licence') . 'licence-1.png', null);
        $response = \common\components\CompanyLicenceReader::send($company);

        $this->assertSame('licence-bytes', $response->content);
        $this->assertSame(\yii\web\Response::FORMAT_RAW, $response->format);
        $this->assertSame('private, no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('http', $response->content);

        $publicCopy = $this->company('company-commercial-licences/old-public.jpg', null);
        $publicResponse = \common\components\CompanyLicenceReader::send($publicCopy);
        $this->assertSame('licence-bytes', $publicResponse->content);

        $legacy = $this->company('commercial-licence/old.jpg', null);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            \common\components\CompanyLicenceReader::send($legacy);
            $this->fail('A legacy licence must stay unavailable.');
        } catch (\yii\web\NotFoundHttpException $e) {
            $this->assertSame(\yii\web\Response::FORMAT_JSON, Yii::$app->response->format);
        }
    }

    public function testPinnedRawErrorHandlerKeepsNotFoundStatus()
    {
        $handler = file_get_contents(dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/web/ErrorHandler.php');
        $this->assertIsString($handler);
        $this->assertStringContainsString(
            "\$response->format === Response::FORMAT_RAW",
            $handler
        );
        $this->assertStringContainsString(
            'convertExceptionToString($exception)',
            $handler
        );

        $response = new \yii\web\Response();
        $response->format = \yii\web\Response::FORMAT_RAW;
        $exception = new \yii\web\NotFoundHttpException('The licence file is unavailable. Upload a replacement.');
        $response->setStatusCodeByException($exception);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertNotSame(500, $response->getStatusCode());
        $this->assertIsString(\yii\web\ErrorHandler::convertExceptionToString($exception));
    }

    public function testCommercialLicenceKeyIsLimitedToStaffAndAdmin()
    {
        $company = $this->company('commercial-licence/old.jpg', 'company-logo/old.jpg');
        $this->installManagers();
        Yii::$app->cloudinaryManager->cloud_name = 'test-cloud';
        Yii::$app->user->switchIdentity(null);

        Yii::$app->id = 'app-candidate';
        $root = dirname(__DIR__, 4);
        require_once $root . '/candidate/models/Company.php';
        require_once $root . '/staff/models/Company.php';
        require_once $root . '/admin/models/Company.php';
        require_once $root . '/company/models/Company.php';
        $candidate = new \candidate\models\Company();
        $candidate->commercial_licence = 'commercial-licence/old.jpg';
        $candidate->company_logo = 'company-logo/old.jpg';
        $candidateFields = $candidate->fields();
        $nestedFields = $company->fields();
        $this->assertArrayNotHasKey('commercial_licence', $candidateFields);
        $this->assertArrayNotHasKey('commercial_licence_url', $candidateFields);
        $this->assertArrayHasKey('company_logo_url', $candidateFields);
        $this->assertArrayNotHasKey('commercial_licence', $nestedFields);
        $this->assertArrayNotHasKey('commercial_licence_url', $nestedFields);
        $this->assertArrayHasKey('company_logo_url', $nestedFields);

        Yii::$app->id = 'app-staff';
        Yii::$app->user->switchIdentity(new \common\models\Staff());
        $staffCompany = new \staff\models\Company();
        $staffCompany->commercial_licence = 'commercial-licence/old.jpg';
        $staffCompany->company_logo = 'company-logo/old.jpg';
        $staffFields = $staffCompany->fields();
        $this->assertArrayHasKey('commercial_licence', $staffFields);
        $this->assertArrayNotHasKey('commercial_licence_url', $staffFields);
        $this->assertArrayHasKey('company_logo_url', $staffFields);

        Yii::$app->id = 'app-admin';
        Yii::$app->user->switchIdentity(new \common\models\Admin());
        $adminCompany = new \admin\models\Company();
        $adminCompany->commercial_licence = 'commercial-licence/old.jpg';
        $adminFields = $adminCompany->fields();
        $this->assertArrayHasKey('commercial_licence', $adminFields);
        $this->assertArrayNotHasKey('commercial_licence_url', $adminFields);

        Yii::$app->id = 'app-company';
        Yii::$app->user->switchIdentity(null);
        $employerFields = $this->fieldNames((new \company\models\Company())->fields());
        $this->assertNotContains('commercial_licence', $employerFields);
        $this->assertNotContains('commercial_licence_url', $employerFields);
        $this->assertContains('company_logo_url', $employerFields);
    }

    private function fieldNames(array $fields): array
    {
        $names = [];
        foreach ($fields as $key => $value) {
            $names[] = is_int($key) ? $value : $key;
        }

        return $names;
    }

    private function boot(): void
    {
        if (!class_exists(Yii::class, false)) {
            $root = dirname(__DIR__, 4);
            require_once $root . '/vendor/autoload.php';
            require_once $root . '/vendor/yiisoft/yii2/Yii.php';
            defined('YII_DEBUG') or define('YII_DEBUG', true);
            defined('YII_ENV') or define('YII_ENV', 'test');
        }
        $root = dirname(__DIR__, 4);
        include $root . '/common/config/bootstrap.php';
        $root = dirname(__DIR__, 4);
        require_once $root . '/common/components/S3FileExistValidator.php';

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
                'user' => [
                    'identityClass' => \common\models\Staff::class,
                    'enableSession' => false,
                    'enableAutoLogin' => false,
                ],
            ],
        ]);
        Yii::$app->user->switchIdentity(new \common\models\Staff());
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
                company_name TEXT,
                company_common_name_en TEXT,
                company_common_name_ar TEXT,
                company_status INTEGER,
                company_followup INTEGER,
                company_hourly_rate TEXT,
                company_bonus_commission TEXT,
                company_email TEXT,
                company_website TEXT,
                company_description_en TEXT,
                company_description_ar TEXT,
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

    private function installManagers(bool $copySucceeds = true, array $existingKeysThatFailCopy = [])
    {
        $managers = new class($copySucceeds, $existingKeysThatFailCopy) {
            public $copies = [];
            public $cloudinaryDeletes = [];
            public $authenticatedHeads = [];
            public $anonymousChecks = [];
            public $sdkHeads = [];
            public $bucket = 'temp-bucket';
            public $cloud_name;
            private $copySucceeds;
            private $existingKeysThatFailCopy;

            public function __construct($copySucceeds, $existingKeysThatFailCopy)
            {
                $this->copySucceeds = $copySucceeds;
                $this->existingKeysThatFailCopy = $existingKeysThatFailCopy;
            }

            public function fileExists($key)
            {
                return $key !== 'missing.png';
            }

            public function anonymousObjectPrivacy($key)
            {
                $this->anonymousChecks[] = $key;
                if (strpos((string) $key, 'public-temp-') !== false) {
                    return 'public';
                }
                if (strpos((string) $key, 'uncertain-') !== false) {
                    return 'uncertain';
                }

                return 'not-public';
            }

            public function objectExistsInBucket($bucket, $key)
            {
                $this->authenticatedHeads[] = [$bucket, $key];

                return $this->fileExists($key);
            }

            public function storedObjectExists($key)
            {
                $this->sdkHeads[] = $key;

                return $this->fileExists($key);
            }

            public function readStoredObject($key)
            {
                if (!$this->storedObjectExists($key)) {
                    throw new \RuntimeException('missing');
                }

                return [
                    'bytes' => 'licence-bytes',
                    'contentType' => 'image/png',
                ];
            }

            public function copy($oldFile, $newFile, $sourceBucket = '', $options = [])
            {
                if (
                    !$this->copySucceeds
                    || $oldFile === 'missing.png'
                    || in_array($oldFile, $this->existingKeysThatFailCopy, true)
                ) {
                    throw new \RuntimeException('copy failed');
                }
                $this->copies[] = [$oldFile, $newFile, $sourceBucket, $options];
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
