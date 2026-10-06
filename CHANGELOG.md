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
* [TASK] Run the test matrix in GitHub Actions
  * Every push and pull request runs code style, linting, composer validation and the BOM check, and the tests for
    the lowest and the newest release of PHPUnit 11, 12 and 13, plus each major on PHP 8.5.
  * `-s composerUpdateMin` installs the lowest releases of the selected PHPUnit major.
* [DOCS] Document usage, limitations and development
  * `README.md` covers the problem, requirements, configuration, the integration point for frameworks and the known
    limitations.
  * `DEVELOPERS.md` covers the runner, the test matrix, how both PHPUnit paths work and the end-to-end fixtures.
* [TASK] Add the GPL-2.0 license text
  * `LICENSE` carries the full license text for the `GPL-2.0-or-later` declared in `composer.json`.
* [TASK] Restrict the 12.x line to PHPUnit 12.5 and PHP 8.3
  * Every package major now supports one PHPUnit major: 13.x on `main`, 12.x and 11.x on the branches `12` and `11`.
  * Requires PHPUnit `^12.5.13` and PHP `^8.3`; the issue trigger resolver of PHPUnit 13.1 and later is removed, the
    subscriber is the only path.
  * `runTests.sh` drops the PHPUnit major switch `-U`, PHPStan uses a single configuration, and the workflow
    `testphpunit12.yml` runs on pull requests with the lowest and newest dependencies on PHP 8.3 and the newest on
    PHP 8.5. `testphpunit11.yml` is a dummy; its real counterpart lives on the branch `11`.
