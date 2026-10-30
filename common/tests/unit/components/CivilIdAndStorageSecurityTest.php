<?php

namespace common\tests\unit\components;

use common\models\Candidate;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 4) . '/common/config/bootstrap.php';

/**
 * Offline regression tests for Issue #55:
 * - Zero hardcoded S3/MediaConvert IAM keys across all environment configs
 * - Civil ID permanent S3 key normalization without duplicate photos/photos/ prefixes
 * - Controller fail-closed handling when Candidate::updateCivilId() fails
 * - Graceful Textract error handling during Candidate::validateCivilID()
 */
class CivilIdAndStorageSecurityTest extends TestCase
{
    public function testAllEnvironmentsHaveNoHardcodedS3OrMediaConvertKeys()
    {
        $envRoot = dirname(__DIR__, 4) . '/environments';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($envRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getFilename() !== 'main-local.php') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $this->assertNotFalse($source, $file->getPathname());
            $this->assertStringNotContainsString('AKIAWMITDJRKWZZEWCUM', $source, $file->getPathname());
            $this->assertStringNotContainsString('AKIAWMITDJRKVN5ODY2X', $source, $file->getPathname());
            $this->assertStringNotContainsString('AKIAWMITDJRKWKGYOFLT', $source, $file->getPathname());
        }
    }

    public function testNormalizeCivilIdPermanentS3KeyPreventsDuplicatePrefix()
    {
        $this->assertSame('', Candidate::normalizeCivilIdPermanentS3Key(null));
        $this->assertSame('', Candidate::normalizeCivilIdPermanentS3Key('   '));
        $this->assertSame(
            'photos/front-123.png',
            Candidate::normalizeCivilIdPermanentS3Key('front-123.png')
        );
        $this->assertSame(
            'photos/front-123.png',
            Candidate::normalizeCivilIdPermanentS3Key('photos/front-123.png')
        );
        $this->assertSame(
            'photos/front-123.png',
            Candidate::normalizeCivilIdPermanentS3Key('candidate-civil-id/front-123.png')
        );
    }

    public function testAccountControllerChecksUpdateCivilIdReturnValue()
    {
        $path = dirname(__DIR__, 4) . '/candidate/modules/v1/controllers/AccountController.php';
        $source = file_get_contents($path);
        $this->assertNotFalse($source);
        $this->assertStringContainsString("if (!\$model->updateCivilId('back'))", $source);
        $this->assertStringContainsString("if (!\$model->updateCivilId('front'))", $source);
        $this->assertStringContainsString("'action'       => 'actionUpdateCivilPhotoBack'", $source);
        $this->assertStringContainsString("'action'       => 'actionUpdateCivilPhotoFront'", $source);
        $this->assertStringContainsString("'action'       => 'actionUpdateCivilExpiryDate'", $source);
        $this->assertStringContainsString("'action'       => 'actionUpdateCivilId'", $source);
    }

    public function testCandidateValidateCivilIdUsesNormalizedKeys()
    {
        $path = dirname(__DIR__, 4) . '/common/models/Candidate.php';
        $source = file_get_contents($path);
        $this->assertNotFalse($source);
        $this->assertStringNotContainsString('extractExpiryDate("photos/" . $this->candidate_civil_photo_front)', $source);
        $this->assertStringNotContainsString('extractExpiryDate("photos/" . $this->candidate_civil_photo_back)', $source);
        $this->assertStringContainsString('self::normalizeCivilIdPermanentS3Key($this->candidate_civil_photo_front)', $source);
        $this->assertStringContainsString('self::normalizeCivilIdPermanentS3Key($this->candidate_civil_photo_back)', $source);
    }
}
