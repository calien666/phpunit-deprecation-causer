<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Integration;

use Calien\PhpUnitDeprecationCauser\DeprecationCauserRegistrar;
use Calien\PhpUnitDeprecationCauser\FirstPartyCode;
use Calien\PhpUnitDeprecationCauser\PassThroughPaths;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * A framework integration as the end-to-end configuration `integration.xml` registers it.
 */
final class IntegrationExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        (new DeprecationCauserRegistrar())->register(
            $configuration,
            $facade,
            new PassThroughPaths(['/Fixtures/ThirdParty/Infrastructure/']),
            [new GeneratedFixtureMapper()],
            [new ConfigurationMessageResolver(FirstPartyCode::fromConfiguration($configuration))],
        );
    }
}
