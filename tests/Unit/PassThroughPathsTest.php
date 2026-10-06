<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\Unit;

use Calien\PhpUnitDeprecationCauser\PassThroughPaths;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;

#[CoversClass(PassThroughPaths::class)]
final class PassThroughPathsTest extends TestCase
{
    #[Test]
    #[DataProvider('matchingFilesProvider')]
    public function matchesFilesOfConfiguredPaths(string $passThroughPaths, string $file, bool $expected): void
    {
        $subject = PassThroughPaths::fromParameters(ParameterCollection::fromArray([
            'passThroughPaths' => $passThroughPaths,
        ]));

        self::assertSame($expected, $subject->matches($file));
    }

    public static function matchingFilesProvider(): Generator
    {
        yield 'file below a configured path' => [
            'passThroughPaths' => '/vendor/acme/factory/',
            'file' => '/app/vendor/acme/factory/src/Factory.php',
            'expected' => true,
        ];
        yield 'configured file' => [
            'passThroughPaths' => '/vendor/acme/lib/src/Utility.php',
            'file' => '/app/vendor/acme/lib/src/Utility.php',
            'expected' => true,
        ];
        yield 'file outside of the configured paths' => [
            'passThroughPaths' => '/vendor/acme/factory/',
            'file' => '/app/vendor/acme/lib/src/Factory.php',
            'expected' => false,
        ];
        yield 'second of several paths, surrounded by blanks' => [
            'passThroughPaths' => '/vendor/acme/factory/ , /vendor/acme/container/',
            'file' => '/app/vendor/acme/container/src/Container.php',
            'expected' => true,
        ];
        yield 'Windows path' => [
            'passThroughPaths' => '/vendor/acme/factory/',
            'file' => 'C:\\app\\vendor\\acme\\factory\\src\\Factory.php',
            'expected' => true,
        ];
    }

    /**
     * @param array<non-empty-string, string> $parameters
     */
    #[Test]
    #[DataProvider('emptyParametersProvider')]
    public function isEmptyWithoutConfiguredPaths(array $parameters): void
    {
        $subject = PassThroughPaths::fromParameters(ParameterCollection::fromArray($parameters));

        self::assertTrue($subject->isEmpty());
        self::assertFalse($subject->matches('/app/vendor/acme/factory/src/Factory.php'));
    }

    public static function emptyParametersProvider(): Generator
    {
        yield 'no parameter' => [
            'parameters' => [],
        ];
        yield 'blank values only' => [
            'parameters' => ['passThroughPaths' => ' , '],
        ];
    }

    #[Test]
    public function mergedPathsMatchFilesOfBoth(): void
    {
        $subject = (new PassThroughPaths(['/vendor/acme/factory/']))
            ->merge(new PassThroughPaths(['/vendor/acme/container/']));

        self::assertFalse($subject->isEmpty());
        self::assertTrue($subject->matches('/app/vendor/acme/factory/src/Factory.php'));
        self::assertTrue($subject->matches('/app/vendor/acme/container/src/Container.php'));
    }
}
