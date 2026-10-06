<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * Finds the file that caused a deprecation through pass-through code, in a stack trace as PHPUnit classifies it:
 * frame 0 holds the file that triggered the deprecation, frame 1 the file that called into it.
 *
 * @phpstan-type StackFrame array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}
 */
final readonly class CausingFileLocator
{
    public function __construct(private PassThroughPaths $passThroughPaths) {}

    /**
     * Returns null when the caller is not pass-through code, PHPUnit's own classification applies then.
     *
     * @param list<StackFrame> $trace
     * @return non-empty-string|null
     */
    public function causingFile(array $trace): ?string
    {
        if (!$this->passThroughPaths->matches($trace[1]['file'] ?? '')) {
            return null;
        }
        foreach (array_slice($trace, 2) as $frame) {
            $file = $frame['file'] ?? '';
            if ($file !== '' && !$this->passThroughPaths->matches($file)) {
                return $file;
            }
        }
        return null;
    }
}
