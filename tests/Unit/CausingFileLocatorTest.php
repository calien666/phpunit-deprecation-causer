<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\Unit;

use Calien\PhpUnitDeprecationCauser\CausingFileLocator;
use Calien\PhpUnitDeprecationCauser\PassThroughPaths;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type StackFrame from CausingFileLocator
 */
#[CoversClass(CausingFileLocator::class)]
final class CausingFileLocatorTest extends TestCase
{
    /**
     * @param list<StackFrame> $trace
     */
    #[Test]
    #[DataProvider('traceProvider')]
    public function findsFileBehindPassThroughCode(array $trace, ?string $expected): void
    {
        $subject = new CausingFileLocator(new PassThroughPaths(['/vendor/acme/container/']));

        self::assertSame($expected, $subject->causingFile($trace));
    }

    public static function traceProvider(): Generator
    {
        yield 'caller is not pass-through code' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'file' => '/app/src/Project.php'],
            ],
            'expected' => null,
        ];
        yield 'file behind one pass-through frame' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'file' => '/app/vendor/acme/container/Container.php'],
                ['function' => 'get', 'file' => '/app/src/Project.php'],
            ],
            'expected' => '/app/src/Project.php',
        ];
        yield 'file behind nested pass-through frames and a frame without file' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'file' => '/app/vendor/acme/container/Container.php'],
                ['function' => 'create', 'file' => '/app/vendor/acme/container/Factory.php'],
                ['function' => '{closure}'],
                ['function' => 'get', 'file' => '/app/vendor/acme/container/Container.php'],
                ['function' => 'run', 'file' => '/app/vendor/acme/lib/Kernel.php'],
            ],
            'expected' => '/app/vendor/acme/lib/Kernel.php',
        ];
        yield 'only pass-through frames' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'file' => '/app/vendor/acme/container/Container.php'],
                ['function' => 'get', 'file' => '/app/vendor/acme/container/Container.php'],
            ],
            'expected' => null,
        ];
        yield 'trace without caller' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
            ],
            'expected' => null,
        ];
    }
}
