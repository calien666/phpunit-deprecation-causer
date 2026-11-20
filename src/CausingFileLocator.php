<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * Finds the file that caused a deprecation through pass-through code or generated code, in a stack trace as PHPUnit
 * classifies it: frame 0 holds the file that triggered the deprecation, frame 1 the file that called into it. The cause
 * resolvers are asked first; a deprecation none of them recognises is left to the stack.
 *
 * @phpstan-type StackFrame array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}
 */
final readonly class CausingFileLocator
{
    /**
     * @param list<GeneratedFileMapper> $generatedFileMappers
     * @param list<CauseResolver> $causeResolvers
     */
    public function __construct(
        private PassThroughPaths $passThroughPaths,
        private array $generatedFileMappers = [],
        private array $causeResolvers = [],
    ) {}

    /**
     * Returns null when neither a cause resolver nor the stack names a cause, PHPUnit's own classification applies
     * then. A third-party cause is returned as the file that triggered the deprecation, which keeps it indirect.
     *
     * @param list<StackFrame> $trace
     * @return non-empty-string|null
     */
    public function causingFile(array $trace, string $message = ''): ?string
    {
        foreach ($this->causeResolvers as $causeResolver) {
            $cause = $causeResolver->cause($trace, $message);
            if ($cause !== null) {
                return $this->causedFile($cause, $trace);
            }
        }
        return $this->causingFileOnStack($trace);
    }

    /**
     * @param list<StackFrame> $trace
     * @return non-empty-string|null
     */
    private function causedFile(Cause $cause, array $trace): ?string
    {
        $file = $cause->file() ?? $trace[0]['file'] ?? '';
        return $file === '' ? null : $this->canonicalFile($file);
    }

    /**
     * @param list<StackFrame> $trace
     * @return non-empty-string|null
     */
    private function causingFileOnStack(array $trace): ?string
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
                return $this->canonicalFile($sourceFile);
            }
        }
        return null;
    }

    /**
     * PHPUnit compares paths as they are, so a file reached through a symbolic link, as the extensions of a test
     * instance often are, has to be named by its real path to count as first-party code.
     *
     * @param non-empty-string $file
     * @return non-empty-string
     */
    private function canonicalFile(string $file): string
    {
        $realPath = realpath($file);
        return $realPath === false ? $file : $realPath;
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
