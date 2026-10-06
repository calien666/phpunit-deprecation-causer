<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd;

use Calien\PhpUnitDeprecationCauser\DeprecationCauserRegistrar;
use Calien\PhpUnitDeprecationCauser\Extension;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Scenarios\DeprecationScenarios;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Runs each scenario of {@see DeprecationScenarios} in its own PHPUnit process with one of the configurations in
 * `Fixtures/`, and checks the deprecations that run reports.
 */
#[CoversClass(Extension::class)]
#[CoversClass(DeprecationCauserRegistrar::class)]
final class ExtensionTest extends TestCase
{
    #[Test]
    #[DataProvider('scenarioProvider')]
    public function reportsDeprecationsCausedByFirstPartyCode(
        string $configuration,
        string $scenario,
        int $expectedDeprecations,
    ): void {
        [$exitCode, $output] = $this->runScenario($configuration, $scenario);

        self::assertMatchesRegularExpression('/^OK \(1 test|^Tests: 1,/m', $output, $output);
        self::assertSame($expectedDeprecations, $this->reportedDeprecations($output), $output);
        self::assertSame($expectedDeprecations > 0 ? 1 : 0, $exitCode, $output);
    }

    public static function scenarioProvider(): Generator
    {
        $expectations = [
            'projectInstantiatesDeprecatedDirectly' => [1, 1, 1],
            'projectInstantiatesDeprecatedThroughContainer' => [1, 0, 1],
            'projectInstantiatesServiceWithDeprecatedDependency' => [1, 0, 1],
            'projectInstantiatesDeprecatedThroughHelperThroughContainer' => [1, 0, 1],
            'testInstantiatesDeprecatedThroughContainer' => [1, 0, 1],
            'thirdPartyInstantiatesDeprecatedThroughContainer' => [0, 0, 1],
            'thirdPartyInstantiatesDeprecatedDirectly' => [0, 0, 1],
            'projectInstantiatesWithoutDeprecation' => [0, 0, 0],
        ];
        $configurations = ['ignoring-indirect', 'ignoring-indirect-without-extension', 'reporting-indirect'];
        foreach ($expectations as $scenario => $expectedDeprecations) {
            foreach ($configurations as $index => $configuration) {
                yield sprintf('%s with %s', $scenario, $configuration) => [
                    'configuration' => $configuration,
                    'scenario' => $scenario,
                    'expectedDeprecations' => $expectedDeprecations[$index],
                ];
            }
        }
    }

    #[Test]
    public function expectedDeprecationCausedByFirstPartyCodeDoesNotFailTheRun(): void
    {
        [$exitCode, $output] = $this->runScenario('ignoring-indirect', 'expectedDeprecationThroughContainer');

        self::assertSame(0, $exitCode, $output);
        self::assertSame(0, $this->reportedDeprecations($output), $output);
    }

    #[Test]
    public function missingPassThroughPathsFailTheRun(): void
    {
        [$exitCode, $output] = $this->runScenario('missing-pass-through-paths', 'projectInstantiatesWithoutDeprecation');

        self::assertSame(1, $exitCode, $output);
        self::assertStringContainsString('No pass-through paths configured', $output);
    }

    /**
     * @return array{int, string}
     */
    private function runScenario(string $configuration, string $scenario): array
    {
        $command = [
            PHP_BINARY,
            dirname(__DIR__, 2) . '/vendor/phpunit/phpunit/phpunit',
            '--configuration',
            __DIR__ . '/Fixtures/' . $configuration . '.xml',
            '--filter',
            sprintf('/::%s$/', $scenario),
        ];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $output = (string)stream_get_contents($pipes[1]);
        $output .= (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output];
    }

    private function reportedDeprecations(string $output): int
    {
        return preg_match('/(?<!PHPUnit )Deprecations: (\d+)/', $output, $matches) === 1 ? (int)$matches[1] : 0;
    }
}
