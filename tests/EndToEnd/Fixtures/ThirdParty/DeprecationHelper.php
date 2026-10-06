<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty;

/**
 * Like Symfony's `trigger_deprecation()`: registered as `<deprecationTrigger>` in the end-to-end configurations.
 */
final class DeprecationHelper
{
    public static function trigger(string $message): void
    {
        trigger_error($message, E_USER_DEPRECATED);
    }
}
