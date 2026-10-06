<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Generated;

use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\DeprecatedService;

/**
 * Stands in for code a framework generated from project code, such as a concatenated cache file. It lies outside
 * of `<source>`; {@see \Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Integration\GeneratedFixtureMapper} maps it back to project code.
 */
final class GeneratedProjectCode
{
    public function instantiateDeprecatedDirectly(): void
    {
        new DeprecatedService();
    }
}
