<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty;

final class DeprecatedService
{
    public function __construct()
    {
        trigger_error('DeprecatedService is deprecated.', E_USER_DEPRECATED);
    }
}
