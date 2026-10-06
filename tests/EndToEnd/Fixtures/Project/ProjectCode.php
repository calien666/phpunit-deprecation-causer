<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Project;

use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\DeprecatedService;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\DeprecatedThroughHelper;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\Infrastructure\Container;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\ThirdPartyCode;

final class ProjectCode
{
    public function instantiateDeprecatedDirectly(): void
    {
        new DeprecatedService();
    }

    public function instantiateDeprecatedThroughContainer(): void
    {
        Container::get(DeprecatedService::class);
    }

    public function instantiateServiceWithDeprecatedDependency(): void
    {
        Container::get(ServiceWithDeprecatedDependency::class);
    }

    public function instantiateDeprecatedThroughHelperThroughContainer(): void
    {
        Container::get(DeprecatedThroughHelper::class);
    }

    public function letThirdPartyInstantiateDeprecatedThroughContainer(): void
    {
        (new ThirdPartyCode())->instantiateDeprecatedThroughContainer();
    }

    public function letThirdPartyInstantiateDeprecatedDirectly(): void
    {
        (new ThirdPartyCode())->instantiateDeprecatedDirectly();
    }

    public function instantiateWithoutDeprecation(): void
    {
        Container::get(self::class);
    }
}
