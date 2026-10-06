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
