<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\Unit;

use Calien\PhpUnitDeprecationCauser\CausingFileLocator;
use Calien\PhpUnitDeprecationCauser\GeneratedFileMapper;
use Calien\PhpUnitDeprecationCauser\MessageCauseResolver;
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

    /**
     * @param list<StackFrame> $trace
     */
    #[Test]
    #[DataProvider('methodTraceProvider')]
    public function findsFileBehindPassThroughMethods(array $trace, ?string $expected): void
    {
        $subject = new CausingFileLocator(new PassThroughPaths(['Acme\\Testing\\TestCase::get']));

        self::assertSame($expected, $subject->causingFile($trace));
    }

    public static function methodTraceProvider(): Generator
    {
        yield 'file behind a frame executing inside a pass-through method' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Lib\\Deprecated', 'file' => '/app/vendor/acme/testing/src/TestCase.php'],
                ['function' => 'get', 'class' => 'Acme\\Testing\\TestCase', 'file' => '/app/tests/ProjectTest.php'],
            ],
            'expected' => '/app/tests/ProjectTest.php',
        ];
        yield 'other method of the same file is not pass-through code' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Lib\\Deprecated', 'file' => '/app/vendor/acme/testing/src/TestCase.php'],
                ['function' => 'setUp', 'class' => 'Acme\\Testing\\TestCase', 'file' => '/app/tests/AbstractProjectTestCase.php'],
            ],
            'expected' => null,
        ];
        yield 'last frame without enclosing method' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Lib\\Deprecated', 'file' => '/app/vendor/acme/testing/src/TestCase.php'],
            ],
            'expected' => null,
        ];
    }

    /**
     * @param list<StackFrame> $trace
     */
    #[Test]
    #[DataProvider('generatedFileTraceProvider')]
    public function mapsGeneratedFilesToTheirSource(array $trace, ?string $expected): void
    {
        $mapper = new class implements GeneratedFileMapper {
            public function sourceFile(string $file, int $line): ?string
            {
                if ($file !== '/app/var/cache/compiled.php') {
                    return null;
                }
                return $line < 100 ? '/app/src/first.php' : '/app/src/second.php';
            }
        };
        $subject = new CausingFileLocator(new PassThroughPaths(['/vendor/acme/container/']), [$mapper]);

        self::assertSame($expected, $subject->causingFile($trace));
    }

    public static function generatedFileTraceProvider(): Generator
    {
        yield 'generated caller' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => 'deprecated', 'file' => '/app/var/cache/compiled.php', 'line' => 120],
            ],
            'expected' => '/app/src/second.php',
        ];
        yield 'generated file behind pass-through code' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'file' => '/app/vendor/acme/container/Container.php'],
                ['function' => 'get', 'file' => '/app/var/cache/compiled.php', 'line' => 12],
            ],
            'expected' => '/app/src/first.php',
        ];
        yield 'caller not generated' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => 'deprecated', 'file' => '/app/vendor/acme/lib/Other.php', 'line' => 12],
            ],
            'expected' => null,
        ];
    }

    /**
     * @param list<StackFrame> $trace
     */
    #[Test]
    #[DataProvider('messageTraceProvider')]
    public function asksMessageCauseResolversWhenTheTraceNamesNoCause(array $trace, string $message, ?string $expected): void
    {
        $resolver = new class implements MessageCauseResolver {
            public function causingFile(string $message): ?string
            {
                return str_contains($message, 'project item') ? '/app/config/project.php' : null;
            }
        };
        $subject = new CausingFileLocator(new PassThroughPaths(['/vendor/acme/container/']), [], [$resolver]);

        self::assertSame($expected, $subject->causingFile($trace, $message));
    }

    public static function messageTraceProvider(): Generator
    {
        yield 'message names first-party configuration' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Migration.php'],
                ['function' => 'migrate', 'file' => '/app/vendor/acme/lib/Kernel.php'],
            ],
            'message' => 'Migrated project item.',
            'expected' => '/app/config/project.php',
        ];
        yield 'message names nothing first-party' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Migration.php'],
                ['function' => 'migrate', 'file' => '/app/vendor/acme/lib/Kernel.php'],
            ],
            'message' => 'Migrated vendor item.',
            'expected' => null,
        ];
        yield 'trace names the cause first' => [
            'trace' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'file' => '/app/vendor/acme/container/Container.php'],
                ['function' => 'get', 'file' => '/app/src/Project.php'],
            ],
            'message' => 'Migrated project item.',
            'expected' => '/app/src/Project.php',
        ];
    }

    #[Test]
    public function resolvesSymbolicLinksOfFilesNamedByMappersAndResolvers(): void
    {
        $link = sys_get_temp_dir() . '/phpunit-deprecation-causer-' . bin2hex(random_bytes(4)) . '.php';
        symlink(__FILE__, $link);
        $mapper = new class ($link) implements GeneratedFileMapper {
            public function __construct(private readonly string $link) {}

            public function sourceFile(string $file, int $line): ?string
            {
                return $file === '/app/var/cache/compiled.php' && $this->link !== '' ? $this->link : null;
            }
        };
        $resolver = new class ($link) implements MessageCauseResolver {
            public function __construct(private readonly string $link) {}

            public function causingFile(string $message): ?string
            {
                return $this->link !== '' ? $this->link : null;
            }
        };
        $subject = new CausingFileLocator(new PassThroughPaths(['/vendor/acme/container/']), [$mapper], [$resolver]);

        $mapped = $subject->causingFile([
            ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
            ['function' => 'deprecated', 'file' => '/app/var/cache/compiled.php', 'line' => 12],
        ]);
        $resolved = $subject->causingFile([
            ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Migration.php'],
            ['function' => 'migrate', 'file' => '/app/vendor/acme/lib/Kernel.php'],
        ], 'Migrated project item.');
        unlink($link);

        self::assertSame(__FILE__, $mapped);
        self::assertSame(__FILE__, $resolved);
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
