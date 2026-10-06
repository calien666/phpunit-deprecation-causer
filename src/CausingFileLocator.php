<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * Finds the file that caused a deprecation through pass-through code or generated code, in a stack trace as PHPUnit
 * classifies it: frame 0 holds the file that triggered the deprecation, frame 1 the file that called into it. When
 * the stack names no cause, the message resolvers are asked.
 *
 * @phpstan-type StackFrame array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}
 */
final readonly class CausingFileLocator
{
    /**
     * @param list<GeneratedFileMapper> $generatedFileMappers
     * @param list<MessageCauseResolver> $messageCauseResolvers
     */
    public function __construct(
        private PassThroughPaths $passThroughPaths,
        private array $generatedFileMappers = [],
        private array $messageCauseResolvers = [],
    ) {}

    /**
     * Returns null when neither the stack nor the message names a cause, PHPUnit's own classification applies
     * then.
     *
     * @param list<StackFrame> $trace
     * @return non-empty-string|null
     */
    public function causingFile(array $trace, string $message = ''): ?string
    {
        return $this->causingFileOnStack($trace) ?? $this->causingFileInMessage($message);
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
     * @return non-empty-string|null
     */
    private function causingFileInMessage(string $message): ?string
    {
        foreach ($this->messageCauseResolvers as $messageCauseResolver) {
            $causingFile = $messageCauseResolver->causingFile($message);
            if ($causingFile !== null) {
                return $this->canonicalFile($causingFile);
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
