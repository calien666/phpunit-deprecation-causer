<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * Finds the file that caused a deprecation through pass-through code or generated code, in a stack trace as PHPUnit
 * classifies it: frame 0 holds the file that triggered the deprecation, frame 1 the file that called into it.
 *
 * @phpstan-type StackFrame array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}
 */
final readonly class CausingFileLocator
{
    /**
     * @param list<GeneratedFileMapper> $generatedFileMappers
     */
    public function __construct(
        private PassThroughPaths $passThroughPaths,
        private array $generatedFileMappers = [],
    ) {}

    /**
     * Returns null when the caller is neither pass-through code nor generated, PHPUnit's own classification applies
     * then.
     *
     * @param list<StackFrame> $trace
     * @return non-empty-string|null
     */
    public function causingFile(array $trace): ?string
    {
        if (!$this->isPassThrough($trace, 1)) {
            return $this->sourceFile($trace[1] ?? null);
        }
        for ($position = 2; $position < count($trace); $position++) {
            $file = $trace[$position]['file'] ?? '';
            if ($file !== '' && !$this->isPassThrough($trace, $position)) {
                return $this->sourceFile($trace[$position]) ?? $file;
            }
        }
        return null;
    }

    /**
     * @param StackFrame|null $frame
     * @return non-empty-string|null
     */
    private function sourceFile(?array $frame): ?string
    {
        $file = $frame['file'] ?? '';
        if ($file === '') {
            return null;
        }
        foreach ($this->generatedFileMappers as $generatedFileMapper) {
            $sourceFile = $generatedFileMapper->sourceFile($file, $frame['line'] ?? 0);
            if ($sourceFile !== null) {
                return $sourceFile;
            }
        }
        return null;
    }

    /**
     * The code of a frame runs inside the function of the next frame, so a frame is pass-through code when its file
     * matches, or when the function it runs in is a pass-through method.
     *
     * @param list<StackFrame> $trace
     */
    private function isPassThrough(array $trace, int $position): bool
    {
        if ($this->passThroughPaths->matches($trace[$position]['file'] ?? '')) {
            return true;
        }
        $enclosing = $trace[$position + 1] ?? null;
        return $enclosing !== null && $this->passThroughPaths->matchesMethod($enclosing['class'] ?? null, $enclosing['function']);
    }
}
