<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Scenarios;

use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Generated\GeneratedProjectCode;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\Project\ProjectCode;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\DeprecatedService;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\Infrastructure\Container;
use Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\Fixtures\ThirdParty\ThirdPartyCode;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Run by {@see \Calien\PhpUnitDeprecationCauser\Tests\EndToEnd\ExtensionTest} in a separate PHPUnit process,
 * one test at a time.
 */
final class DeprecationScenarios extends TestCase
{
    #[Test]
    public function projectInstantiatesDeprecatedDirectly(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->instantiateDeprecatedDirectly();
    }

    #[Test]
    public function projectInstantiatesDeprecatedThroughContainer(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->instantiateDeprecatedThroughContainer();
    }

    #[Test]
    public function projectInstantiatesServiceWithDeprecatedDependency(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->instantiateServiceWithDeprecatedDependency();
    }

    #[Test]
    public function projectInstantiatesDeprecatedThroughHelperThroughContainer(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->instantiateDeprecatedThroughHelperThroughContainer();
    }

    #[Test]
    public function testInstantiatesDeprecatedThroughContainer(): void
    {
        $this->expectNotToPerformAssertions();
        Container::get(DeprecatedService::class);
    }

    #[Test]
    public function thirdPartyInstantiatesDeprecatedThroughContainer(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->letThirdPartyInstantiateDeprecatedThroughContainer();
    }

    #[Test]
    public function thirdPartyInstantiatesDeprecatedDirectly(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->letThirdPartyInstantiateDeprecatedDirectly();
    }

    #[Test]
    public function projectInstantiatesWithoutDeprecation(): void
    {
        $this->expectNotToPerformAssertions();
        (new ProjectCode())->instantiateWithoutDeprecation();
    }

    #[Test]
    public function generatedProjectCodeInstantiatesDeprecatedDirectly(): void
    {
        $this->expectNotToPerformAssertions();
        (new GeneratedProjectCode())->instantiateDeprecatedDirectly();
    }

    #[Test]
    public function thirdPartyMigratesProjectConfiguration(): void
    {
        $this->expectNotToPerformAssertions();
        (new ThirdPartyCode())->migrateConfiguration('project-item');
    }

    #[Test]
    public function thirdPartyMigratesVendorConfiguration(): void
    {
        $this->expectNotToPerformAssertions();
        (new ThirdPartyCode())->migrateConfiguration('vendor-item');
    }

    #[Test]
    #[IgnoreDeprecations]
    public function expectedDeprecationThroughContainer(): void
    {
        $this->expectUserDeprecationMessage('DeprecatedService is deprecated.');
        (new ProjectCode())->instantiateDeprecatedThroughContainer();
    }
}
