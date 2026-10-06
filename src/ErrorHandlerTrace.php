<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

use PHPUnit\Runner\ErrorHandler;

/**
 * Cuts the stack trace PHPUnit classifies a deprecation by out of the backtrace of a subscriber that PHPUnit's
 * error handler notifies.
 *
 * @phpstan-import-type StackFrame from CausingFileLocator
 * @phpstan-type DeprecationTriggers array{functions: list<non-empty-string>, methods: list<non-empty-string>, ...}
 */
final readonly class ErrorHandlerTrace
{
    /**
     * @param DeprecationTriggers $deprecationTriggers
     */
    public function __construct(private array $deprecationTriggers) {}

    /**
     * Returns an empty list when the deprecation did not come through the error handler, for instance when a
     * test in a separate process reported it.
     *
     * @param list<StackFrame> $backtrace
     * @return list<StackFrame>
     */
    public function cut(array $backtrace): array
    {
        $errorHandlerFrames = array_keys(array_filter(
            $backtrace,
            static fn(array $frame): bool => ($frame['class'] ?? null) === ErrorHandler::class,
        ));
        if ($errorHandlerFrames === []) {
            return [];
        }
        $trace = array_slice($backtrace, max($errorHandlerFrames) + 1);
        return array_slice($trace, $this->deprecationTriggerPosition($trace) ?? 0);
    }

    /**
     * The outermost frame of the first sequence of deprecation trigger frames, like PHPUnit 12.5 and later
     * determine it.
     *
     * @param list<StackFrame> $trace
     */
    private function deprecationTriggerPosition(array $trace): ?int
    {
        $position = null;
        foreach ($trace as $currentPosition => $frame) {
            if ($this->isDeprecationTrigger($frame)) {
                $position = $currentPosition;
                continue;
            }
            if ($position !== null) {
                break;
            }
        }
        return $position;
    }

    /**
     * @param StackFrame $frame
     */
    private function isDeprecationTrigger(array $frame): bool
    {
        if (!isset($frame['class'])) {
            return in_array($frame['function'], $this->deprecationTriggers['functions'], true);
        }
        return in_array($frame['class'] . '::' . $frame['function'], $this->deprecationTriggers['methods'], true);
    }
}
