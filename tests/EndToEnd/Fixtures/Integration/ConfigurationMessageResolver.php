<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Integration;

use Calien\PhpUnitDeprecationCauser\FirstPartyCode;
use Calien\PhpUnitDeprecationCauser\MessageCauseResolver;

final readonly class ConfigurationMessageResolver implements MessageCauseResolver
{
    public function __construct(private FirstPartyCode $firstPartyCode) {}

    public function causingFile(string $message): ?string
    {
        if (preg_match('/of configuration item \'([^\']+)\'/', $message, $matches) !== 1) {
            return null;
        }
        foreach ($this->firstPartyCode->directories() as $directory) {
            $file = $directory . '/Configuration/' . $matches[1] . '.php';
            if (is_file($file) && $this->firstPartyCode->includes($file)) {
                return $file;
            }
        }
        return null;
    }
}
