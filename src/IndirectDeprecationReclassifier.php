<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\Event\Code\IssueTrigger\Code;
use PHPUnit\Event\Code\IssueTrigger\IssueTrigger;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Facade;
use PHPUnit\Event\Test\DeprecationTriggered;
use PHPUnit\Event\Test\DeprecationTriggeredSubscriber;
use PHPUnit\TextUI\Configuration\SourceFilter;

/**
 * Reports an indirect deprecation again as a direct one when first-party or test code caused it through
 * pass-through code. Used by {@see Extension} before PHPUnit 13.1, which offers no issue trigger resolvers.
 *
 * Subscribers are notified synchronously from PHPUnit's error handler, so the stack that triggered the
 * deprecation is still available.
 */
final readonly class IndirectDeprecationReclassifier implements DeprecationTriggeredSubscriber
{
    public function __construct(
        private CausingFileLocator $causingFileLocator,
        private ErrorHandlerTrace $errorHandlerTrace,
    ) {}

    public function notify(DeprecationTriggered $event): void
    {
        if (!$event->trigger()->isIndirect()) {
            return;
        }
        $causingFile = $this->causingFileLocator->causingFile(
            $this->errorHandlerTrace->cut(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
        );
        $caller = $causingFile === null ? null : $this->categorize($causingFile, $event->test());
        if ($caller === null || !$caller->isFirstPartyOrTest()) {
            return;
        }
        Facade::emitter()->testTriggeredDeprecation(
            $event->test(),
            $event->message(),
            $event->file(),
            $event->line(),
            $event->wasSuppressed(),
            $event->ignoredByBaseline(),
            $event->ignoredByTest(),
            IssueTrigger::from(Code::ThirdParty, $caller),
            $event->stackTrace(),
        );
    }

    /**
     * Same categories as PHPUnit's error handler, without the PHPUnit category a caller cannot have here.
     */
    private function categorize(string $file, Test $test): Code
    {
        if ($file === $test->file()) {
            return Code::Test;
        }
        if (SourceFilter::instance()->includes($file)) {
            return Code::FirstParty;
        }
        return Code::ThirdParty;
    }
}
