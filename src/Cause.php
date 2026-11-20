<?php

declare(strict_types=1);

namespace Calien\PhpUnitDeprecationCauser;

/**
 * The answer of a {@see CauseResolver} for a deprecation it recognises.
 */
final readonly class Cause
{
    /**
     * @param non-empty-string|null $file
     */
    private function __construct(private ?string $file) {}

    /**
     * PHPUnit classifies the deprecation by this file as its caller.
     *
     * @param non-empty-string $file
     */
    public static function inFile(string $file): self
    {
        return new self($file);
    }

    /**
     * Keeps the deprecation indirect, even when a frame of the project triggered it.
     */
    public static function thirdParty(): self
    {
        return new self(null);
    }

    /**
     * @return non-empty-string|null Null for a third-party cause
     */
    public function file(): ?string
    {
        return $this->file;
    }
}
