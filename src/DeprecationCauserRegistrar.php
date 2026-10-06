<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\Runner\Extension\Facade;
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
    /**
     * The facade is unused with PHPUnit 13; the signature is the same for every supported PHPUnit major, so an
     * integration calls it alike.
     */
    public function register(Configuration $configuration, Facade $facade, PassThroughPaths $passThroughPaths): void
    {
        if (!$configuration->source()->ignoreIndirectDeprecations()) {
            return;
        }
        IssueTriggerResolver::register(new CausingFileLocator($passThroughPaths));
    }
}
