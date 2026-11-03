<?php

namespace common\tests\unit\security;

class PublicDebugEndpointTest extends \Codeception\Test\Unit
{
    public function testPublicPhpinfoEndpointsAreNotPresent()
    {
        $root = dirname(__DIR__, 4);
        $publicPaths = [
            $root . '/info.php',
            $root . '/admin/web',
            $root . '/candidate/web',
            $root . '/company/web',
            $root . '/inspector/web',
            $root . '/staff/web',
            $root . '/status/web',
            $root . '/verification/web',
        ];

        foreach ($this->collectPhpFiles($publicPaths) as $file) {
            $source = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression(
                '/\bphpinfo\s*\(/i',
                $source,
                $file . ' must not expose phpinfo output from a public document root.'
            );
        }
    }

    public function testDockerBuildContextExcludesLocalSecrets()
    {
        $root = dirname(__DIR__, 4);
        $entries = array_map('trim', file($root . '/.dockerignore', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $entries = array_values(array_filter($entries, static function ($entry) {
            return $entry !== '' && strpos($entry, '#') !== 0;
        }));

        $this->assertContains('.env', $entries);
        $this->assertContains('.env.*', $entries);
        $this->assertContains('info.php', $entries);
    }

    private function collectPhpFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
