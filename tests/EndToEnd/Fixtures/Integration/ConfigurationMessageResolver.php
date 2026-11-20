<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Integration;

use Calien\PhpUnitDeprecationCauser\Cause;
use Calien\PhpUnitDeprecationCauser\CauseResolver;
use Calien\PhpUnitDeprecationCauser\FirstPartyCode;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\ConfigurationMigration;

final readonly class ConfigurationMessageResolver implements CauseResolver
{
    public function __construct(private FirstPartyCode $firstPartyCode) {}

    public function cause(array $trace, string $message): ?Cause
    {
        $item = $this->migratedItem($trace, $message);
        if ($item === null) {
            return null;
        }
        foreach ($this->firstPartyCode->directories() as $directory) {
            $file = $directory . '/Configuration/' . $item . '.php';
            if (is_file($file) && $this->firstPartyCode->includes($file)) {
                return Cause::inFile($file);
            }
        }
        return Cause::thirdParty();
    }

    /**
     * @param list<array{function: string, class?: class-string, args?: list<mixed>}> $trace
     */
    private function migratedItem(array $trace, string $message): ?string
    {
        if (preg_match('/of configuration item \'([^\']+)\'/', $message, $matches) === 1) {
            return $matches[1];
        }
        if ($message !== 'Migrated the deprecated option of a configuration item.') {
            return null;
        }
        foreach ($trace as $frame) {
            if (($frame['class'] ?? null) === ConfigurationMigration::class && $frame['function'] === 'migrateUnnamed') {
                $item = $frame['args'][0] ?? null;
                return is_string($item) ? $item : null;
            }
        }
        return null;
    }
}
