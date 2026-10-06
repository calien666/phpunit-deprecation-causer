<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Project;

use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\DeprecatedService;

final readonly class ServiceWithDeprecatedDependency
{
    public function __construct(public DeprecatedService $deprecatedService) {}
}
