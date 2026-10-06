# Developing the extension

Everything runs in the TYPO3 core-testing containers through `Build/Scripts/runTests.sh`; no PHP or Composer is
needed on the host. `Build/Scripts/runTests.sh -h` lists all suites and options.

## Branches

Every major of the package supports one PHPUnit major and lives on its own branch: `main` is 13.x for PHPUnit 13,
`12` and `11` are the branches for PHPUnit 12 and 11. Changes reach every branch through pull requests only; they need
an approving review and passing checks, and are merged by rebase. A fix for all majors goes to `main` first and is
backported to `12` and `11` in pull requests of their own.

## Installing dependencies

```shell
Build/Scripts/runTests.sh -s composerUpdate
```

`-s composerUpdateMin` installs the lowest supported releases instead.

## Suites

| Suite                                  | What it runs                                                           |
| :------------------------------------- | :--------------------------------------------------------------------- |
| `-s unit`                              | Unit tests and end-to-end tests                                        |
| `-s phpstan`                           | PHPStan                                                                |
| `-s cgl` (`-n` for a dry run)          | php-cs-fixer with the TYPO3 core rule set                              |
| `-s lintPhp`                           | PHP syntax check                                                       |
| `-s composerValidate`, `-s checkBom`   | Integrity checks                                                       |

Pass options for PHPUnit or PHPStan after `--`, for instance `Build/Scripts/runTests.sh -s unit -- --filter Helper`.

The CI workflow runs the lowest and the newest dependencies on PHP 8.2 and the newest on PHP 8.5. A change counts as
done when those lanes pass.

## How it works

PHPUnit's error handler classifies a userland deprecation by frame 0 (the file that triggered it) and frame 1 (the
file that called into it). `CausingFileLocator` returns the first file behind the pass-through frames when frame 1
is pass-through code.

PHPUnit 11 offers no issue trigger resolvers to hand that file to PHPUnit, so `IndirectDeprecationReclassifier`
subscribes to `DeprecationTriggered`. Subscribers are notified synchronously from the error handler, so
`ErrorHandlerTrace` can cut the original stack out of `debug_backtrace()`. When the file is first-party or test code,
the subscriber emits the deprecation again with a direct trigger through PHPUnit's internal event emitter.
`DeprecationCauserRegistrar` registers the subscriber.

## End-to-end tests

`tests/EndToEnd/ExtensionTest.php` runs each scenario of `Fixtures/Scenarios/DeprecationScenarios.php` in its own
PHPUnit process, with one of the configurations in `tests/EndToEnd/Fixtures/`:

| Configuration                             | Purpose                                                          |
| :---------------------------------------- | :--------------------------------------------------------------- |
| `ignoring-indirect.xml`                   | The extension at work                                            |
| `ignoring-indirect-without-extension.xml` | Reproduces the suppressed deprecations without the extension     |
| `reporting-indirect.xml`                  | Proves nothing is reported twice when PHPUnit reports everything |
| `missing-pass-through-paths.xml`          | A registration without paths fails the run                       |
| `integration.xml`                         | A framework integration through `DeprecationCauserRegistrar`     |

`Fixtures/Project/` is first-party code, `Fixtures/ThirdParty/` third-party code, and
`Fixtures/ThirdParty/Infrastructure/` the pass-through code of those configurations. `Fixtures/Integration/` holds
the framework integration of `integration.xml`, and `Fixtures/Generated/` the generated code it maps back to
first-party code. Its message resolver attributes configuration migrations to `Fixtures/Project/Configuration/` and
leaves those of `Fixtures/ThirdParty/Configuration/` suppressed.
