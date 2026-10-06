<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\IssueTriggerResolver\Resolver;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Turns reporting of deprecations caused through pass-through code on for a test run. {@see Extension} uses it
 * with the configured paths; a framework integration calls it from its own PHPUnit extension with the paths of
 * its framework, merged with the configured ones.
 *
 * Without `<source ignoreIndirectDeprecations="true">` there is nothing to do: PHPUnit reports every
 * deprecation then.
 */
final class DeprecationCauserRegistrar
{
    public function register(Configuration $configuration, Facade $facade, PassThroughPaths $passThroughPaths): void
    {
        if (!$configuration->source()->ignoreIndirectDeprecations()) {
            return;
        }
        $causingFileLocator = new CausingFileLocator($passThroughPaths);
        if (interface_exists(Resolver::class)) {
            IssueTriggerResolver::register($causingFileLocator);
            return;
        }
        $facade->registerSubscriber(new IndirectDeprecationReclassifier(
            $causingFileLocator,
            new ErrorHandlerTrace($configuration->source()->deprecationTriggers()),
        ));
    }
}
