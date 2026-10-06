<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\Runner\ErrorHandler;
use PHPUnit\Runner\IssueTriggerResolver\Resolution;
use PHPUnit\Runner\IssueTriggerResolver\Resolver;

/**
 * Reports the file that caused a deprecation as its caller, from PHPUnit 13.1 on.
 * Registered by {@see Extension}.
 *
 * @phpstan-import-type StackFrame from CausingFileLocator
 */
final readonly class IssueTriggerResolver implements Resolver
{
    public function __construct(private CausingFileLocator $causingFileLocator) {}

    public static function register(CausingFileLocator $causingFileLocator): void
    {
        ErrorHandler::instance()->addIssueTriggerResolver(new self($causingFileLocator));
    }

    /**
     * @param list<StackFrame> $trace
     */
    public function resolve(array $trace, string $message): ?Resolution
    {
        $callee = $trace[0]['file'] ?? '';
        $caller = $this->causingFileLocator->causingFile($trace, $message);
        if ($callee === '' || $caller === null) {
            return null;
        }
        return new Resolution($callee, $caller);
    }
}
