<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\Unit;

use Calien\PhpUnitDeprecationCauser\FirstPartyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FirstPartyCode::class)]
final class FirstPartyCodeTest extends TestCase
{
    #[Test]
    public function namesTheConfiguredSourceDirectories(): void
    {
        $subject = new FirstPartyCode(['/app/src', '/app/packages']);

        self::assertSame(['/app/src', '/app/packages'], $subject->directories());
    }

    #[Test]
    public function namesDirectoriesWithoutTrailingSlash(): void
    {
        $subject = new FirstPartyCode(['/app/src/', '/app/packages//']);

        self::assertSame(['/app/src', '/app/packages'], $subject->directories());
    }

    #[Test]
    public function includesFilesGivenWithRedundantSlashes(): void
    {
        $subject = new FirstPartyCode([]);

        self::assertTrue($subject->includes(dirname(__DIR__, 2) . '/src//Extension.php'));
    }

    /**
     * `Build/phpunit/UnitTests.xml` includes `src/` and nothing else.
     */
    #[Test]
    public function includesFilesAsTheRunningPhpUnitConfigurationDoes(): void
    {
        $subject = new FirstPartyCode([]);

        self::assertTrue($subject->includes(dirname(__DIR__, 2) . '/src/Extension.php'));
        self::assertFalse($subject->includes(__FILE__));
    }
}
