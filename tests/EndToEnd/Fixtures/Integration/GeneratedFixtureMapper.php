<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Integration;

use Calien\PhpUnitDeprecationCauser\GeneratedFileMapper;

final class GeneratedFixtureMapper implements GeneratedFileMapper
{
    public function sourceFile(string $file, int $line): ?string
    {
        if (!str_ends_with($file, '/Fixtures/Generated/GeneratedProjectCode.php')) {
            return null;
        }
        return dirname(__DIR__) . '/Project/ProjectCode.php';
    }
}
