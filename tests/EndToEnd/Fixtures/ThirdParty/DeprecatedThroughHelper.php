<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty;

final class DeprecatedThroughHelper
{
    public function __construct()
    {
        DeprecationHelper::trigger('DeprecatedThroughHelper is deprecated.');
    }
}
