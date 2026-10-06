# Developing the extension

Everything runs in the TYPO3 core-testing containers through `Build/Scripts/runTests.sh`; no PHP or Composer is
needed on the host. `Build/Scripts/runTests.sh -h` lists all suites and options.

## Installing a PHPUnit major

The extension is tested against PHPUnit 11, 12 and 13. Install the major to work on, with a matching PHP version:

```shell
Build/Scripts/runTests.sh -p 8.2 -U 11 -s composerUpdate
Build/Scripts/runTests.sh -p 8.3 -U 12 -s composerUpdate
Build/Scripts/runTests.sh -p 8.4 -U 13 -s composerUpdate
```

`-s composerUpdateMin` installs the lowest supported release of the major instead. `composer.json` keeps its
spanning constraint either way.

## Suites

| Suite                                  | What it runs                                                           |
| :------------------------------------- | :--------------------------------------------------------------------- |
| `-s unit`                              | Unit tests and end-to-end tests                                        |
| `-s phpstan`                           | PHPStan, with the configuration of the installed PHPUnit major         |
| `-s cgl` (`-n` for a dry run)          | php-cs-fixer with the TYPO3 core rule set                              |
| `-s lintPhp`                           | PHP syntax check                                                       |
| `-s composerValidate`, `-s checkBom`   | Integrity checks                                                       |

Pass options for PHPUnit or PHPStan after `--`, for instance `Build/Scripts/runTests.sh -s unit -- --filter Helper`.

The CI workflow runs the lowest and the newest release of every major, and every major on PHP 8.5. A change counts
as done when those lanes pass; PHPStan runs on the newest releases only.

## How it works

PHPUnit's error handler classifies a userland deprecation by frame 0 (the file that triggered it) and frame 1 (the
file that called into it). `CausingFileLocator` returns the first file behind the pass-through frames when frame 1
is pass-through code. How that file reaches PHPUnit differs per release:

- **PHPUnit 13.1 and later**: `IssueTriggerResolver` implements PHPUnit's issue trigger resolver interface and
  returns that file as the caller. PHPUnit then classifies it itself.
- **Before 13.1**: `IndirectDeprecationReclassifier` subscribes to `DeprecationTriggered`. Subscribers are notified
  synchronously from the error handler, so `ErrorHandlerTrace` can cut the original stack out of
  `debug_backtrace()`. When the file is first-party or test code, the subscriber emits the deprecation again with
  a direct trigger through PHPUnit's internal event emitter.

`DeprecationCauserRegistrar` picks the path. The PHPStan configuration of each major excludes the class of the other
path.

## End-to-end tests

`tests/EndToEnd/ExtensionTest.php` runs each scenario of `Fixtures/Scenarios/DeprecationScenarios.php` in its own
PHPUnit process, with one of the configurations in `tests/EndToEnd/Fixtures/`:

| Configuration                             | Purpose                                                          |
| :---------------------------------------- | :--------------------------------------------------------------- |
| `ignoring-indirect.xml`                   | The extension at work                                            |
| `ignoring-indirect-without-extension.xml` | Reproduces the suppressed deprecations without the extension     |
| `reporting-indirect.xml`                  | Proves nothing is reported twice when PHPUnit reports everything |
| `missing-pass-through-paths.xml`          | A registration without paths fails the run                       |

`Fixtures/Project/` is first-party code, `Fixtures/ThirdParty/` third-party code, and
`Fixtures/ThirdParty/Infrastructure/` the pass-through code of those configurations.
