<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\Infrastructure;

use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Project\ServiceWithDeprecatedDependency;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\DeprecatedService;

/**
 * Stands in for `GeneralUtility::makeInstance()` and a dependency injection container: a pass-through path in
 * the end-to-end configurations.
 */
final class Container
{
    /**
     * @param class-string $className
     */
    public static function get(string $className): object
    {
        if ($className === ServiceWithDeprecatedDependency::class) {
            return new ServiceWithDeprecatedDependency(self::createDeprecatedService());
        }
        return new $className();
    }

    private static function createDeprecatedService(): DeprecatedService
    {
        return new DeprecatedService();
    }
}
