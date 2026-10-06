<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\Unit;

use Calien\PhpUnitDeprecationCauser\CausingFileLocator;
use Calien\PhpUnitDeprecationCauser\ErrorHandlerTrace;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\ErrorHandler;

/**
 * @phpstan-import-type StackFrame from CausingFileLocator
 */
#[CoversClass(ErrorHandlerTrace::class)]
final class ErrorHandlerTraceTest extends TestCase
{
    /**
     * @param array{functions: list<non-empty-string>, methods: list<non-empty-string>} $deprecationTriggers
     * @param list<StackFrame> $backtrace
     * @param list<StackFrame> $expected
     */
    #[Test]
    #[DataProvider('backtraceProvider')]
    public function cutsTraceAsPhpUnitClassifiesIt(array $deprecationTriggers, array $backtrace, array $expected): void
    {
        $subject = new ErrorHandlerTrace($deprecationTriggers);

        self::assertSame($expected, $subject->cut($backtrace));
    }

    public static function backtraceProvider(): Generator
    {
        $subscriberFrames = [
            ['function' => 'notify', 'class' => 'Acme\\Subscriber', 'file' => '/app/vendor/phpunit/phpunit/src/Event/Dispatcher/DirectDispatcher.php'],
            ['function' => 'testTriggeredDeprecation', 'class' => 'PHPUnit\\Event\\DispatchingEmitter', 'file' => '/app/vendor/phpunit/phpunit/src/Runner/ErrorHandler.php'],
            ['function' => '__invoke', 'class' => ErrorHandler::class],
        ];
        $noTriggers = ['functions' => [], 'methods' => []];
        yield 'frames below the error handler' => [
            'deprecationTriggers' => $noTriggers,
            'backtrace' => [
                ...$subscriberFrames,
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Deprecated', 'file' => '/app/src/Project.php'],
            ],
            'expected' => [
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Deprecated', 'file' => '/app/src/Project.php'],
            ],
        ];
        yield 'from the outermost deprecation trigger method on' => [
            'deprecationTriggers' => ['functions' => [], 'methods' => ['Acme\\Deprecation::trigger']],
            'backtrace' => [
                ...$subscriberFrames,
                ['function' => 'trigger_error', 'file' => '/app/vendor/acme/lib/Deprecation.php'],
                ['function' => 'trigger', 'class' => 'Acme\\Deprecation', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Deprecated', 'file' => '/app/src/Project.php'],
            ],
            'expected' => [
                ['function' => 'trigger', 'class' => 'Acme\\Deprecation', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Deprecated', 'file' => '/app/src/Project.php'],
            ],
        ];
        yield 'from the outermost deprecation trigger function on' => [
            'deprecationTriggers' => ['functions' => ['trigger_deprecation'], 'methods' => []],
            'backtrace' => [
                ...$subscriberFrames,
                ['function' => 'trigger_error', 'file' => '/app/vendor/symfony/deprecation-contracts/function.php'],
                ['function' => 'trigger_deprecation', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Deprecated', 'file' => '/app/src/Project.php'],
            ],
            'expected' => [
                ['function' => 'trigger_deprecation', 'file' => '/app/vendor/acme/lib/Deprecated.php'],
                ['function' => '__construct', 'class' => 'Acme\\Deprecated', 'file' => '/app/src/Project.php'],
            ],
        ];
        yield 'not notified by the error handler' => [
            'deprecationTriggers' => $noTriggers,
            'backtrace' => [
                ['function' => 'notify', 'class' => 'Acme\\Subscriber', 'file' => '/app/vendor/phpunit/phpunit/src/Event/Dispatcher/DirectDispatcher.php'],
                ['function' => 'forward', 'class' => 'PHPUnit\\Event\\Facade', 'file' => '/app/vendor/phpunit/phpunit/src/Framework/TestRunner/SeparateProcessTestRunner.php'],
            ],
            'expected' => [],
        ];
    }
}
