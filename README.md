[![Latest Stable Version](https://poser.pugx.org/calien/phpunit-deprecation-causer/v/stable.svg?style=for-the-badge)](https://packagist.org/packages/calien/phpunit-deprecation-causer)
[![License](https://poser.pugx.org/calien/phpunit-deprecation-causer/license?style=for-the-badge)](https://packagist.org/packages/calien/phpunit-deprecation-causer)
[![PHPUnit 13](https://img.shields.io/badge/PHPUnit-13-green.svg?style=for-the-badge)](https://phpunit.de/announcements/phpunit-13.html)
[![PHPUnit 12](https://img.shields.io/badge/PHPUnit-12-green.svg?style=for-the-badge)](https://phpunit.de/announcements/phpunit-12.html)
[![PHPUnit 11](https://img.shields.io/badge/PHPUnit-11-green.svg?style=for-the-badge)](https://phpunit.de/announcements/phpunit-11.html)
[![Total Downloads](https://poser.pugx.org/calien/phpunit-deprecation-causer/downloads.svg?style=for-the-badge)](https://packagist.org/packages/calien/phpunit-deprecation-causer)
[![Monthly Downloads](https://poser.pugx.org/calien/phpunit-deprecation-causer/d/monthly?style=for-the-badge)](https://packagist.org/packages/calien/phpunit-deprecation-causer)

# PHPUnit extension `calien/phpunit-deprecation-causer`

|                 | URL                                                               |
|-----------------|-------------------------------------------------------------------|
| **Repository:** | https://github.com/calien666/phpunit-deprecation-causer           |
| **Packagist:**  | https://packagist.org/packages/calien/phpunit-deprecation-causer  |
| **ISSUES:**     | https://github.com/calien666/phpunit-deprecation-causer/issues/   |
| **RELEASES:**   | https://github.com/calien666/phpunit-deprecation-causer/releases/ |

## Description

A PHPUnit extension that keeps `failOnDeprecation="true"` meaningful when `ignoreIndirectDeprecations="true"` is set:
deprecations your own code causes through a factory or a dependency injection container are reported again, while
deprecations that third-party code triggers among itself stay suppressed.

## The problem

With `<source ignoreIndirectDeprecations="true">`, PHPUnit decides by two stack frames: the file that triggered the
deprecation and the file that called into it. When both are third-party code, the deprecation is suppressed.

That also hides deprecations your code caused, whenever a factory or container sits in between:

```php
// Reported: your code calls the deprecated constructor.
new DeprecatedService();

// Suppressed: the container calls the deprecated constructor on your behalf.
$container->get(DeprecatedService::class);
```

The same applies to a service of yours that gets a deprecated service injected. The extension walks past the
configured pass-through files and lets PHPUnit classify the first frame behind them instead. If that frame is
first-party code (inside `<source>`) or the test itself, PHPUnit reports the deprecation as one your code caused.

## Compatibility

Every major of the package supports exactly one PHPUnit major. Configuration and integration point are the same in
all of them.

| Branch | State       | Composer Package Name             | Version    | PHPUnit           | PHP                |
|--------|-------------|-----------------------------------|------------|-------------------|--------------------|
| main   | development | calien/phpunit-deprecation-causer | 13.0.x-dev | 13.1 and later    | 8.4, 8.5           |
| 12     | development | calien/phpunit-deprecation-causer | 12.0.x-dev | 12.5.13 and later | 8.3, 8.4, 8.5      |
| 11     | development | calien/phpunit-deprecation-causer | 11.0.x-dev | 11.5.54 and later | 8.2, 8.3, 8.4, 8.5 |

## Installation

Install the version matching the PHPUnit major of the project as development dependency:

```bash
# PHPUnit 13
composer require --dev 'calien/phpunit-deprecation-causer':'13.0.*@dev'

# PHPUnit 12
composer require --dev 'calien/phpunit-deprecation-causer':'12.0.*@dev'

# PHPUnit 11
composer require --dev 'calien/phpunit-deprecation-causer':'11.0.*@dev'
```

> [!IMPORTANT]
> No version is released yet. Once `13.0.0`, `12.0.0` and `11.0.0` are tagged, require `^13`, `^12` or `^11`.

## Configuration

Register the extension and name the pass-through paths, comma-separated. A file is pass-through code when its path
contains one of the fragments:

```xml
<phpunit failOnDeprecation="true">
  <source ignoreIndirectDeprecations="true">
    <include>
      <directory>src/</directory>
    </include>
  </source>
  <extensions>
    <bootstrap class="Calien\PhpUnitDeprecationCauser\Extension">
      <parameter name="passThroughPaths" value="/vendor/acme/container/,/var/cache/container/"/>
    </bootstrap>
  </extensions>
</phpunit>
```

List only code that instantiates or calls on behalf of its caller. Everything else stays subject to PHPUnit's own
classification.

When a file also contains code of its own, name the single method instead, as `Fqcn::method`. Only frames that run
inside that method count as pass-through code then:

```xml
<parameter name="passThroughPaths" value="/vendor/acme/container/,Acme\Testing\TestCase::get"/>
```

The extension does nothing when `ignoreIndirectDeprecations` is off: PHPUnit reports every deprecation then.

A deprecation reported this way is a regular deprecation: `failOnDeprecation`,
`displayDetailsOnTestsThatTriggerDeprecations` and `#[IgnoreDeprecations]` apply to it as to any other.
`<deprecationTrigger>` entries are respected.

## Framework integrations

The package knows no framework. An integration ships its pass-through paths in its own PHPUnit extension and hands
them to `DeprecationCauserRegistrar`, merged with the ones configured by the project:

```php
use Calien\PhpUnitDeprecationCauser\DeprecationCauserRegistrar;
use Calien\PhpUnitDeprecationCauser\PassThroughPaths;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class AcmeFrameworkExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $passThroughPaths = (new PassThroughPaths(['/vendor/acme/framework/src/Container/']))
            ->merge(PassThroughPaths::fromParameters($parameters));
        (new DeprecationCauserRegistrar())->register($configuration, $facade, $passThroughPaths);
    }
}
```

## Limitations

- **Resolution started by third-party code** is not attributed: when a framework instantiates your service and that
  service needs a deprecated dependency, no frame of your code is on the stack.
- **Deprecations about configuration**, such as a framework migrating your configuration at runtime, carry no frame
  of your code either. Attributing them needs framework knowledge and belongs into a framework integration.
- **Native PHP deprecations** (`E_DEPRECATED`) are left to PHPUnit; only `E_USER_DEPRECATED` is handled.
- **Tests in separate processes** are not covered: the extension is not bootstrapped in the child process.
- PHPUnit 12 offers no issue trigger resolvers, so the extension reports the deprecation again from an event
  subscriber through PHPUnit's internal event emitter. That call can change in a minor PHPUnit release.

## Development

See [DEVELOPERS.md](DEVELOPERS.md).
