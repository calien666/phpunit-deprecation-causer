# Changelog

### Unreleased

* [TASK] Add containerized build and code quality tooling
  * `Build/Scripts/runTests.sh`, modelled on the TYPO3 core runner, runs every suite in the
    `ghcr.io/typo3/core-testing-php*` images: `unit`, `cgl`, `phpstan`, `lintPhp`, `checkBom` and the composer
    suites.
  * `-s composerUpdate -U <11|12|13>` installs the selected PHPUnit major without changing `composer.json`.
  * Code style follows the TYPO3 core php-cs-fixer rule set, PHPStan runs on level `max` with the PHPUnit and
    strict rules, with one configuration per PHPUnit major.
* [FEATURE] Configure pass-through paths
  * `PassThroughPaths` names the files that only call code on behalf of their caller, such as factories and
    dependency injection containers, as path fragments.
  * It reads the comma-separated extension parameter `passThroughPaths` and merges with the paths a framework
    integration ships, so the package itself carries no framework knowledge.
* [FEATURE] Report deprecations caused by first-party code through pass-through code
  * `Extension` is registered in `<extensions>`; with `<source ignoreIndirectDeprecations="true">` it reports a
    deprecation that is triggered in third-party code when the first frame behind pass-through code is first-party
    code or the test itself. `failOnDeprecation`, baselines and `#[IgnoreDeprecations]` apply to it as to any
    other deprecation; deprecations among third-party code stay suppressed.
  * PHPUnit 13.1 and later get an issue trigger resolver; older releases a subscriber that reports the
    deprecation again as a direct one.
  * `DeprecationCauserRegistrar` is the entry point for framework integrations with their own PHPUnit extension.
  * Supports PHPUnit `^11.5.54 || ^12.5.13 || ^13.0.4`, the first releases of each major with the issue trigger
    API the extension builds on.
