<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty;

use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\Infrastructure\Container;

final class ThirdPartyCode
{
    public function instantiateDeprecatedThroughContainer(): void
    {
        Container::get(DeprecatedService::class);
    }

    public function instantiateDeprecatedDirectly(): void
    {
        new DeprecatedService();
    }
}
