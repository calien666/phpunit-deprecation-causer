<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use Calien\PhpUnitDeprecationCauser\Exception\MissingPassThroughPathsException;
use PHPUnit\Runner\Extension\Extension as PhpUnitExtension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Reports deprecations that first-party or test code causes through pass-through code, such as a factory or a
 * dependency injection container, although `<source ignoreIndirectDeprecations="true">` is set.
 *
 * Register it in the `<extensions>` section of the PHPUnit configuration with the comma-separated parameter
 * `passThroughPaths`.
 */
final class Extension implements PhpUnitExtension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $passThroughPaths = PassThroughPaths::fromParameters($parameters);
        if ($passThroughPaths->isEmpty()) {
            throw new MissingPassThroughPathsException(
                'No pass-through paths configured, set the parameter "passThroughPaths".',
                1791286043,
            );
        }
        (new DeprecationCauserRegistrar())->register($configuration, $facade, $passThroughPaths);
    }
}
