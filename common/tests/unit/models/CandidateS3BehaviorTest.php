<?php

namespace common\tests\unit\models;

use Yii;
use common\models\Candidate;
use common\components\S3ResourceManager;

class CandidateS3BehaviorTestModel extends Candidate
{
    public static function primaryKey()
    {
        return ['candidate_id'];
    }

    public function attributes()
    {
        return [
            'candidate_id',
            'candidate_resume',
            'candidate_civil_photo_front',
            'candidate_civil_photo_back',
            'candidate_civil_need_verification',
        ];
    }
}

class CandidateS3BehaviorResourceManager extends S3ResourceManager
{
    /**
     * Exposes S3 URL parsing for focused regression tests.
     * @param string $filenameOrUrl
     * @return array
     */
    public function resolveLocation($filenameOrUrl)
    {
        return $this->resolveObjectLocation($filenameOrUrl);
    }
}

class CandidateS3BehaviorTest extends \Codeception\Test\Unit
{
    protected $tester;

    public function testDeleteCivilIdUsesPhotosPrefixAndFieldSpecificErrors()
    {
        $candidate = new CandidateS3BehaviorTestModel();
        $candidate->candidate_id = 1;
        $candidate->setOldAttributes([
            'candidate_id' => 1,
            'candidate_civil_photo_front' => 'front.jpg',
            'candidate_civil_photo_back' => 'back.jpg',
        ]);

        $originalManager = Yii::$app->get('resourceManager');

        $trackingManager = new class extends \yii\base\Component {
            public $deleted = [];

            public function delete($name)
            {
                $this->deleted[] = $name;
                return true;
            }
        };

        try {
            Yii::$app->set('resourceManager', $trackingManager);
            $candidate->clearErrors();

            $this->assertTrue($candidate->deleteFile('civil-id', 'front'));
            $this->assertSame(['photos/front.jpg'], $trackingManager->deleted);

            $failingManager = new class extends \yii\base\Component {
                public function delete($name)
                {
                    throw new \Exception('delete failed');
                }
            };

            Yii::$app->set('resourceManager', $failingManager);
            $candidate->clearErrors();

            $this->assertFalse($candidate->deleteFile('civil-id', 'back'));
            $this->assertArrayHasKey('candidate_civil_photo_back', $candidate->getErrors());
            $this->assertArrayNotHasKey('candidate_resume', $candidate->getErrors());
        } finally {
            Yii::$app->set('resourceManager', $originalManager);
        }
    }

    /**
     * Verifies old Civil ID files are deleted only after callers finish saving.
     */
    public function testUpdateCivilIdCopiesAndDefersOldFileDeletionUntilAfterSave()
    {
        $candidate = new CandidateS3BehaviorTestModel();
        $candidate->candidate_id = 1;
        $candidate->candidate_civil_photo_front = 'new-front.jpg';
        $candidate->setOldAttributes([
            'candidate_id' => 1,
            'candidate_civil_photo_front' => 'old-front.jpg',
        ]);

        $originalManager = Yii::$app->get('resourceManager');
        $originalTempManager = Yii::$app->get('temporaryBucketResourceManager');

        $resourceManager = new class extends \yii\base\Component {
            public $operations = [];
            public $exists = true;

            public function copy($oldFile, $newFile, $sourceBucket = "", $options = [])
            {
                $this->operations[] = ['copy', $oldFile, $newFile, $sourceBucket];
                return true;
            }

            public function fileExists($name)
            {
                $this->operations[] = ['fileExists', $name];
                return $this->exists;
            }

            public function delete($name)
            {
                $this->operations[] = ['delete', $name];
                return true;
            }
        };

        $tempManager = new class extends \yii\base\Component {
            public $bucket = 'temp-upload-bucket';
        };

        try {
            Yii::$app->set('resourceManager', $resourceManager);
            Yii::$app->set('temporaryBucketResourceManager', $tempManager);

            $candidate->clearErrors();
            $this->assertTrue($candidate->updateCivilId('front'));
            $this->assertSame([
                ['copy', 'new-front.jpg', 'photos/new-front.jpg', 'temp-upload-bucket'],
                ['fileExists', 'photos/new-front.jpg'],
            ], $resourceManager->operations);

            $this->assertTrue($candidate->deletePendingCivilIdFiles('front'));
            $this->assertSame([
                ['copy', 'new-front.jpg', 'photos/new-front.jpg', 'temp-upload-bucket'],
                ['fileExists', 'photos/new-front.jpg'],
                ['delete', 'photos/old-front.jpg'],
            ], $resourceManager->operations);

            $candidate->candidate_civil_photo_front = 'verify-miss.jpg';
            $candidate->setOldAttributes([
                'candidate_id' => 1,
                'candidate_civil_photo_front' => 'old-front.jpg',
            ]);
            $candidate->clearErrors();

            $resourceManager->operations = [];
            $resourceManager->exists = false;

            $this->assertFalse($candidate->updateCivilId('front'));
            $this->assertSame([
                ['copy', 'verify-miss.jpg', 'photos/verify-miss.jpg', 'temp-upload-bucket'],
                ['fileExists', 'photos/verify-miss.jpg'],
            ], $resourceManager->operations);
            $this->assertArrayHasKey('candidate_civil_photo_front', $candidate->getErrors());
        } finally {
            Yii::$app->set('resourceManager', $originalManager);
            Yii::$app->set('temporaryBucketResourceManager', $originalTempManager);
        }
    }

    /**
     * Verifies S3 object resolution handles both URL styles and encoded keys.
     */
    public function testS3ResourceManagerResolvesPathStyleAndEncodedUrls()
    {
        $manager = new CandidateS3BehaviorResourceManager([
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'eu-west-1',
            'bucket' => 'default-bucket',
        ]);

        $this->assertSame(
            ['candidate-bucket', 'photos/front id.jpg'],
            $manager->resolveLocation('https://candidate-bucket.s3.eu-west-1.amazonaws.com/photos/front%20id.jpg')
        );

        $this->assertSame(
            ['candidate-bucket', 'photos/back id.jpg'],
            $manager->resolveLocation('https://s3.eu-west-1.amazonaws.com/candidate-bucket/photos/back%20id.jpg')
        );
    }
}
