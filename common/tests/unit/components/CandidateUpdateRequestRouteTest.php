<?php

namespace common\tests\unit\components;

use common\models\CandidateNotification;
use common\models\CandidateUpdateRequest;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;
use yii\web\HttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Exercises the real Staff and Student controllers through Yii routing and bearer auth.
 * Skips unless UPDATE_REQUEST_TEST_DSN points at a non-production MySQL database.
 */
class CandidateUpdateRequestRouteTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!getenv('UPDATE_REQUEST_TEST_DSN')) {
            return;
        }
        if (class_exists('Yii', false) && Yii::$app) {
            Yii::$app = null;
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
        require_once $root . '/console/migrations/m261015_140000_candidate_update_requests.php';

        new \yii\console\Application([
            'id' => 'update-request-route-schema',
            'basePath' => $root . '/common',
            'aliases' => ['@common' => $root . '/common'],
            'components' => [
                'db' => self::dbConfig(),
                'i18n' => self::i18nConfig(),
            ],
        ]);
        self::installSchema();
        Yii::$app = null;
    }

    protected function setUp(): void
    {
        if (!getenv('UPDATE_REQUEST_TEST_DSN')) {
            $this->markTestSkipped('Disposable database DSN was not provided.');
        }
        $this->bootDatabase();
        Yii::$app->db->createCommand()->delete('candidate_notification')->execute();
        Yii::$app->db->createCommand()->delete('candidate_update_request_upload')->execute();
        Yii::$app->db->createCommand()->delete('candidate_update_request')->execute();
    }

    public function testUnauthenticatedAndWrongRoleAreRejected()
    {
        $this->assertSame(401, $this->dispatch('staff', 'GET', '/v1/candidates/update-requests/1', null)['status']);
        $this->assertSame(401, $this->dispatch('candidate', 'GET', '/v1/account/update-requests', null)['status']);
        $this->assertSame(401, $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'candidate-a', ['type' => 'profile_photo'])['status']);
        $this->assertSame(401, $this->dispatch('candidate', 'GET', '/v1/account/update-requests', 'staff-token')['status']);
    }

    public function testStudentSeesOnlyTheirOwnRequestAndCannotMarkAnotherRead()
    {
        $created = $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'staff-token', [
            'type' => 'profile_photo',
        ]);
        $this->assertSame(200, $created['status'], json_encode($created));
        $this->assertFalse($created['body']['already_pending']);

        $owner = $this->dispatch('candidate', 'GET', '/v1/account/update-requests', 'candidate-a');
        $other = $this->dispatch('candidate', 'GET', '/v1/account/update-requests', 'candidate-b');
        $this->assertSame('profile_photo', $owner['body']['requests'][0]['type']);
        $this->assertSame([], $other['body']['requests']);

        $uuid = CandidateNotification::find()->select('cn_uuid')->scalar();
        $marked = $this->dispatch(
            'candidate',
            'PATCH',
            '/v1/candidate-notifications/mark-read/' . $uuid,
            'candidate-b'
        );
        $this->assertSame(404, $marked['status']);
        $this->assertSame(1, (int) CandidateNotification::find()->select('is_new')->scalar());
    }

    public function testDuplicateStaffPostDoesNotCreateAnotherNotification()
    {
        $first = $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'staff-token', [
            'type' => 'civil_id',
        ]);
        $second = $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'staff-token', [
            'type' => 'civil_id',
        ]);

        $this->assertSame(200, $first['status'], json_encode($first));
        $this->assertSame(200, $second['status'], json_encode($second));
        $this->assertFalse($first['body']['already_pending']);
        $this->assertTrue($second['body']['already_pending']);
        $this->assertSame($first['body']['request']['id'], $second['body']['request']['id']);
        $this->assertSame(1, (int) CandidateNotification::find()->count());
        $this->assertArrayNotHasKey('staged_front_key', $first['body']['request']);
        $this->assertArrayNotHasKey('candidate_civil_id', $owner = $this->dispatch('candidate', 'GET', '/v1/account/update-requests', 'candidate-a')['body']['requests'][0]);
    }

    public function testCivilFinalSaveRouteRejectsAStaleSubmissionAndCommitsTheMatchingRevision()
    {
        $created = $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'staff-token', [
            'type' => 'civil_id',
        ]);
        $this->assertSame(200, $created['status'], json_encode($created));

        $this->attachStorage();
        $candidate = \common\models\Candidate::findOne(1);
        \common\components\CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-front.jpg');
        \common\components\CandidateUpdateRequestService::recordEligibleUpload(1, 'civil_id', 'new-back.jpg');
        $this->assertSame('success', \common\components\CandidateUpdateRequestService::stageCivilSide($candidate, 'front', 'new-front.jpg')['operation']);
        $this->assertSame('success', \common\components\CandidateUpdateRequestService::stageCivilSide($candidate, 'back', 'new-back.jpg')['operation']);
        $request = \common\components\CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID);

        $missingToken = $this->dispatch('candidate', 'POST', '/v1/account/update-civil-id-expiry-date', 'candidate-a', [
            'civil_id' => '200000000009',
            'civil_expiry_date' => '2031-06-01',
        ]);
        $this->assertSame(200, $missingToken['status'], json_encode($missingToken));
        $this->assertSame('error', $missingToken['body']['operation']);
        $this->assertStringContainsString('out of date', $missingToken['body']['message']);
        $unchanged = \common\models\Candidate::findOne(1);
        $this->assertSame('200000000001', (string) $unchanged->candidate_civil_id);
        $this->assertSame('old-front.jpg', $unchanged->candidate_civil_photo_front);
        $this->assertSame(
            $request->cur_uuid,
            \common\components\CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID)->cur_uuid
        );

        $saved = $this->dispatch('candidate', 'POST', '/v1/account/update-civil-id-expiry-date', 'candidate-a', [
            'civil_id' => '200000000009',
            'civil_expiry_date' => '2031-06-01',
            'update_request_id' => $request->cur_uuid,
            'staging_revision' => (int) $request->staging_revision,
        ]);
        $this->assertSame(200, $saved['status'], json_encode($saved));
        $this->assertSame('success', $saved['body']['operation'], json_encode($saved));
        $committed = \common\models\Candidate::findOne(1);
        $this->assertSame('200000000009', (string) $committed->candidate_civil_id);
        $this->assertSame('2031-06-01', (string) $committed->candidate_civil_expiry_date);
        $this->assertSame('new-front.jpg', $committed->candidate_civil_photo_front);
        $this->assertSame('new-back.jpg', $committed->candidate_civil_photo_back);
        $this->assertNull(\common\components\CandidateUpdateRequestService::pending(1, CandidateUpdateRequest::TYPE_CIVIL_ID));

        $replay = $this->dispatch('candidate', 'POST', '/v1/account/update-civil-id-expiry-date', 'candidate-a', [
            'civil_id' => '200000000010',
            'civil_expiry_date' => '2032-02-02',
            'update_request_id' => $request->cur_uuid,
            'staging_revision' => (int) $request->staging_revision,
        ]);
        $this->assertSame('error', $replay['body']['operation']);
        $afterReplay = \common\models\Candidate::findOne(1);
        $this->assertSame('200000000009', (string) $afterReplay->candidate_civil_id);
        $this->assertSame('new-front.jpg', $afterReplay->candidate_civil_photo_front);
    }

    public function testCustomNotificationRouteIsCandidateScopedAndLeavesDocumentRequests()
    {
        $photo = $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'staff-token', [
            'type' => CandidateUpdateRequest::TYPE_PROFILE_PHOTO,
        ]);
        $civil = $this->dispatch('staff', 'POST', '/v1/candidates/update-request/1', 'staff-token', [
            'type' => CandidateUpdateRequest::TYPE_CIVIL_ID,
        ]);
        $message = 'Please update your IBAN; the current details are incorrect.';
        $key = '22222222-2222-4222-8222-222222222222';
        $denied = $this->dispatch('staff', 'POST', '/v1/candidates/custom-notification/1', null, [
            'message' => $message,
            'send_key' => $key,
        ]);
        $sent = $this->dispatch('staff', 'POST', '/v1/candidates/custom-notification/1', 'staff-token', [
            'message' => $message,
            'send_key' => $key,
        ]);
        $repeat = $this->dispatch('staff', 'POST', '/v1/candidates/custom-notification/1', 'staff-token', [
            'message' => $message,
            'send_key' => $key,
        ]);

        $this->assertSame(401, $denied['status']);
        $this->assertSame(200, $sent['status'], json_encode($sent));
        $this->assertSame('success', $sent['body']['operation']);
        $this->assertFalse($sent['body']['already_sent']);
        $this->assertTrue($repeat['body']['already_sent']);
        $this->assertSame(1, (int) CandidateNotification::find()->where([
            'type' => CandidateNotification::TYPE_STAFF_MESSAGE,
        ])->count());

        $owner = $this->dispatch('candidate', 'GET', '/v1/candidate-notifications', 'candidate-a');
        $other = $this->dispatch('candidate', 'GET', '/v1/candidate-notifications', 'candidate-b');
        $ownerItems = isset($owner['body']['items']) ? $owner['body']['items'] : $owner['body'];
        $otherItems = isset($other['body']['items']) ? $other['body']['items'] : $other['body'];
        $ownerMessages = array_column($ownerItems, 'message');
        $this->assertContains($message, $ownerMessages);
        $this->assertNotContains($message, array_column($otherItems ?: [], 'message'));

        $blocked = $this->dispatch('candidate', 'PATCH', '/v1/candidate-notifications/mark-read/' . $key, 'candidate-b');
        $read = $this->dispatch('candidate', 'PATCH', '/v1/candidate-notifications/mark-read/' . $key, 'candidate-a');
        $saved = CandidateNotification::findOne(['cn_uuid' => $key]);
        $this->assertSame(404, $blocked['status']);
        $this->assertSame(200, $read['status']);
        $this->assertSame(0, (int) $saved->is_new);
        $this->assertSame($message, $saved->message);
        $this->assertSame(CandidateUpdateRequest::STATUS_PENDING, $photo['body']['request']['status']);
        $this->assertSame(CandidateUpdateRequest::STATUS_PENDING, $civil['body']['request']['status']);
        $this->assertSame(2, (int) CandidateUpdateRequest::find()->count());
    }

    /**
     * @param string $appName
     * @param string $method
     * @param string $url
     * @param string|null $token
     * @param array|null $body
     * @return array{status:int, body:array|null}
     */
    private function dispatch($appName, $method, $url, $token, $body = null)
    {
        if (Yii::$app) {
            Yii::$app = null;
        }
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $url;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = '/index.php';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($token) {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $root = dirname(__DIR__, 4);
        $config = [
            'id' => $appName . '-route-test',
            'basePath' => $root . '/' . ($appName === 'staff' ? 'staff' : 'candidate'),
            'bootstrap' => ['log'],
            'aliases' => [
                '@common' => $root . '/common',
                '@staff' => $root . '/staff',
                '@candidate' => $root . '/candidate',
            ],
            'modules' => [
                'v1' => [
                    'class' => $appName === 'staff'
                        ? 'staff\modules\v1\Module'
                        : 'candidate\modules\v1\Module',
                ],
            ],
            'params' => [
                'allowedOrigins' => ['*'],
            ],
            'components' => [
                'db' => self::dbConfig(),
                'i18n' => self::i18nConfig(),
                'log' => [
                    'targets' => [],
                ],
                'request' => [
                    'enableCookieValidation' => false,
                    'scriptUrl' => '/index.php',
                    'parsers' => [
                        'application/json' => 'yii\web\JsonParser',
                    ],
                ],
                'user' => [
                    'identityClass' => $appName === 'staff' ? 'common\models\Staff' : 'candidate\models\Candidate',
                    'enableAutoLogin' => false,
                    'enableSession' => false,
                    'loginUrl' => null,
                ],
                'urlManager' => [
                    'enablePrettyUrl' => true,
                    'enableStrictParsing' => true,
                    'showScriptName' => false,
                    'rules' => $appName === 'staff' ? [[
                        'class' => 'yii\rest\UrlRule',
                        'controller' => 'v1/candidate',
                        'patterns' => [
                            'GET update-requests/<id>' => 'list-update-requests',
                            'POST update-request/<id>' => 'create-update-request',
                            'POST custom-notification/<id>' => 'send-custom-notification',
                        ],
                    ]] : [
                        [
                            'class' => 'yii\rest\UrlRule',
                            'controller' => 'v1/account',
                            'pluralize' => false,
                            'patterns' => [
                                'GET update-requests' => 'update-requests',
                                'POST update-civil-id-expiry-date' => 'update-civil-id-expiry-date',
                            ],
                        ],
                        [
                            'class' => 'yii\rest\UrlRule',
                            'controller' => 'v1/candidate-notification',
                            'patterns' => [
                                'GET' => 'list',
                                'PATCH mark-read/<id>' => 'mark-read',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $application = new Application($config);
        $this->attachStorage($application);
        if ($body !== null) {
            $application->request->setRawBody(json_encode($body));
            $application->request->headers->set('Content-Type', 'application/json');
        }

        try {
            $response = $application->handleRequest($application->request);
            $data = $response->data;
            $status = (int) $response->statusCode;
        } catch (UnauthorizedHttpException $e) {
            $data = null;
            $status = 401;
        } catch (HttpException $e) {
            $data = ['message' => $e->getMessage()];
            $status = (int) $e->statusCode;
        }

        Yii::$app = null;
        $this->bootDatabase();

        return [
            'status' => $status,
            'body' => is_array($data) ? $data : null,
        ];
    }

    private function bootDatabase()
    {
        if (Yii::$app) {
            Yii::$app = null;
        }
        $root = dirname(__DIR__, 4);
        new \yii\console\Application([
            'id' => 'update-request-route-db',
            'basePath' => $root . '/common',
            'aliases' => ['@common' => $root . '/common'],
            'components' => [
                'db' => self::dbConfig(),
                'i18n' => self::i18nConfig(),
            ],
        ]);
    }

    private static function dbConfig()
    {
        return [
            'class' => 'yii\db\Connection',
            'dsn' => getenv('UPDATE_REQUEST_TEST_DSN'),
            'username' => getenv('UPDATE_REQUEST_TEST_USER') ?: 'root',
            'password' => getenv('UPDATE_REQUEST_TEST_PASSWORD') ?: 'root',
            'charset' => 'utf8mb4',
        ];
    }

    private static function i18nConfig()
    {
        return [
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
        ];
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
            'candidate_token',
            'staff_token',
            'blocked_ip',
            'candidate',
            'staff',
        ] as $table) {
            $db->createCommand('DROP TABLE IF EXISTS ' . $db->quoteTableName($table))->execute();
        }
        $db->createCommand('SET FOREIGN_KEY_CHECKS=1')->execute();
        $db->createCommand('CREATE TABLE blocked_ip (
            ip_uuid CHAR(60) NULL,
            ip_address VARCHAR(45) NULL
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE staff (
            staff_id INT NOT NULL PRIMARY KEY,
            staff_name VARCHAR(255) NULL,
            staff_email VARCHAR(255) NULL,
            deleted TINYINT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE staff_token (
            token_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            token_value VARCHAR(255) NOT NULL,
            token_device VARCHAR(255) NULL,
            token_device_id VARCHAR(255) NULL,
            token_status INT NOT NULL,
            token_last_used_datetime DATETIME NULL,
            token_expiry_datetime DATETIME NULL,
            token_created_datetime DATETIME NULL,
            ip_address VARCHAR(45) NULL,
            otp VARCHAR(20) NULL,
            total_attempt INT NULL
        ) ENGINE=InnoDB')->execute();
        $db->createCommand('CREATE TABLE candidate (
            candidate_id INT NOT NULL PRIMARY KEY,
            candidate_uid VARCHAR(64) NULL,
            candidate_name VARCHAR(255) NULL,
            candidate_name_ar VARCHAR(255) NULL,
            candidate_email VARCHAR(255) NULL,
            candidate_email_verification TINYINT NOT NULL DEFAULT 1,
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
        $db->createCommand('CREATE TABLE candidate_token (
            token_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            candidate_id INT NOT NULL,
            token_value VARCHAR(255) NOT NULL,
            token_device VARCHAR(255) NULL,
            token_device_id VARCHAR(255) NULL,
            token_status INT NOT NULL,
            token_last_used_datetime DATETIME NULL,
            token_expiry_datetime DATETIME NULL,
            token_created_datetime DATETIME NULL,
            ip_address VARCHAR(45) NULL,
            otp VARCHAR(20) NULL,
            total_attempt INT NULL
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
        $db->createCommand()->insert('staff', [
            'staff_id' => 1,
            'staff_name' => 'Test Staff',
            'staff_email' => 'staff@example.test',
        ])->execute();
        $db->createCommand()->insert('staff_token', [
            'staff_id' => 1,
            'token_value' => 'staff-token',
            'token_status' => 1,
        ])->execute();
        foreach ([1 => 'candidate-a', 2 => 'candidate-b'] as $id => $token) {
            $db->createCommand()->insert('candidate', [
                'candidate_id' => $id,
                'candidate_name' => 'Candidate ' . $id,
                'candidate_email' => 'candidate' . $id . '@example.test',
                'candidate_email_verification' => 1,
                'candidate_personal_photo' => 'old-photo.jpg',
                'candidate_civil_id' => '20000000000' . $id,
                'candidate_civil_expiry_date' => '2030-01-01',
                'candidate_civil_photo_front' => 'old-front.jpg',
                'candidate_civil_photo_back' => 'old-back.jpg',
                'candidate_civil_need_verification' => 0,
                'deleted' => 0,
            ])->execute();
            $db->createCommand()->insert('candidate_token', [
                'candidate_id' => $id,
                'token_value' => $token,
                'token_status' => 1,
            ])->execute();
        }
        $migration = new \m261015_140000_candidate_update_requests();
        $migration->up();
    }

    /**
     * @param \yii\base\Application|null $application
     * @return void
     */
    private function attachStorage($application = null)
    {
        $app = $application ?: Yii::$app;
        $app->set('resourceManager', new class extends \yii\base\Component {
            public $bucket = 'route-test';

            public function copy($oldFile, $newFile, $sourceBucket = '', $options = [])
            {
            }

            public function delete($name)
            {
            }
        });
        $app->set('temporaryBucketResourceManager', new class extends \yii\base\Component {
            public $bucket = 'temp-test';
        });
    }
}
